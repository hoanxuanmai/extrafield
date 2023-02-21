<?php
/**
 * Created by Vincent
 * @author vincent@pixodeo.net
 */

namespace HXM\ExtraField\Actions;

use HXM\ExtraField\Contracts\CanMakeExtraFieldInterface;
use HXM\ExtraField\Contracts\CanAccessExtraFieldValueInterface;
use HXM\ExtraField\Contracts\ExtraFieldTypeEnumInterface;
use HXM\ExtraField\Exceptions\DeniedAccessExtraFieldValueException;
use HXM\ExtraField\ExtraFieldValueValidation;
use HXM\ExtraField\Models\ExtraField;
use HXM\ExtraField\Services\ExtraFieldService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SaveExtraFieldValueForTargetAction
{
    public CanAccessExtraFieldValueInterface $target;

    protected Collection $dataInput;
    protected Collection $extraValues;
    protected Collection $extraValuesExist;
    protected ExtraFieldTypeEnumInterface $extraFieldTypeEnumInstance;
    protected CanMakeExtraFieldInterface $extraFieldTypeInstance;
    protected array $resolved = [];

    /**
     * @throws \Exception
     */
    public function __construct(Model $target)
    {
        $this->setTarget($target);
    }

    /**
     * @param Model $target
     * @return $this
     * @throws DeniedAccessExtraFieldValueException
     */
    public function setTarget(Model $target): self
    {
        if (! $target instanceof CanAccessExtraFieldValueInterface) {
            throw new DeniedAccessExtraFieldValueException(get_class($target));
        }
        $this->target = $target;

        $this->extraFieldTypeInstance = $target->getExtraFieldTargetTypeInstance();
        $this->extraFieldTypeEnumInstance = \HXM\ExtraField\ExtraField::getEnumInstance(get_class($this->extraFieldTypeInstance));
        return $this;
    }

    /**
     * @param ExtraFieldValueValidation $data
     * @param \Closure|null $filters
     * @param string $errorBag
     * @return CanAccessExtraFieldValueInterface
     * @throws ValidationException
     */
    function handle(ExtraFieldValueValidation $data, \Closure $filters = null): CanAccessExtraFieldValueInterface
    {
        $this->dataInput = collect($data->data());

        $this->extraValues = collect();

        if ($filters) {
            $this->extraValuesExist = $this->target->extraValues()->where($filters)->get();
        } else {
            $this->extraValuesExist = $this->target->extraValues;
        }
        ExtraFieldService::getAllFieldsByTypeInstance($this->extraFieldTypeInstance)
            ->when($filters, function(Collection $list) use ($filters){
                return $list->filter($filters);
            })
            ->each(function(ExtraField $field) {
                $this->appendDataSaveFromFieldInstance($field);
            });

        $deleteValues = $this->extraValuesExist->diff($this->extraValues)->pluck('id');
        if ($deleteValues->count()) {
            $this->target->extraValues()
                ->getQuery()
                ->withoutGlobalScopes()
                ->whereIn('id', $deleteValues->toArray())
                ->delete();
        }


        return $this->target;
    }

    /**
     * @param ExtraField $field
     * @param $isMultiple
     * @return void
     */
    protected function appendDataSaveFromFieldInstance(ExtraField $field, $isMultiple = false): void
    {
        if ($this->extraFieldTypeEnumInstance::requireHasFields($field->type)) {
            $field->fields->each(function($childField) use ($field){
                return $this->appendDataSaveFromFieldInstance($childField, $this->extraFieldTypeEnumInstance::inputRequestIsMultiple($field->type));
            });
        } else {
            if ($isMultiple) {
                $this->resolved[] = $field->inputName;
                $values = Arr::get($this->dataInput, $field->parentInput);
                foreach ($values as $row => $groupValues) {

                    if ($this->extraFieldTypeEnumInstance::inputRequestHasFile($field->type)) {
                        $value = $this->target->handleSaveExtraValueIsFile(Arr::get($groupValues, $field->slug));
                    } else {
                        $value = \HXM\ExtraField\ExtraField::getValueProcessionInstance($this->target->getMorphClass())->setValue(Arr::get($groupValues, $field->slug), $field->type, $field);
                    }
                    $dataSave = [
                        'extraFieldId' => $field->id,
                        'value' => $value,
                        'row' =>$row
                    ];
                    $this->storeValueToDatabase($field, $dataSave);
                }
            } elseif (!in_array($field->inputName, $this->resolved)) {
                if ($this->extraFieldTypeEnumInstance::inputRequestHasFile($field->type) && $document = $this->target->handleSaveExtraValueIsFile(Arr::get($this->dataInput, $field->inputName))) {
                    $value = $document;
                } else {
                    $value = \HXM\ExtraField\ExtraField::getValueProcessionInstance($this->target->getMorphClass())->setValue(Arr::get($this->dataInput, $field->inputName), $field->type, $field);
                }

                $dataSave = [
                    'extraFieldId' => $field->id,
                    'value' => $value
                ];
                $this->storeValueToDatabase($field, $dataSave);
            }
        }
    }

    protected function storeValueToDatabase($field, $dataSave): void
    {
        $valueInstance = $this->extraValuesExist
            ->when(isset($dataSave['row']), function(Collection $dt) use ($dataSave){
                return $dt->where('row', $dataSave['row']);
            })
            ->firstWhere('extraFieldId', $field->id);
        if ($valueInstance) {
            $valueInstance->update($dataSave);
        } else {
            $valueInstance = $this->target->extraValues()
                ->make()
                ->setRelation('tempType', $field->type)
                ->fill($dataSave);
            $valueInstance->save();
        }
        $this->extraValues->push($valueInstance);
    }
}
