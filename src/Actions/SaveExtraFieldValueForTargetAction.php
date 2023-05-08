<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Actions;

use HXM\ExtraField\Contracts\CanMakeExtraFieldInterface;
use HXM\ExtraField\Contracts\CanAccessExtraFieldValueInterface;
use HXM\ExtraField\Contracts\ExtraFieldTypeEnumInterface;
use HXM\ExtraField\Exceptions\DeniedAccessExtraFieldValueException;
use HXM\ExtraField\ExtraFieldValueValidation;
use HXM\ExtraField\Models\ExtraField;
use HXM\ExtraField\Models\ExtraFieldValue;
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
    protected array $updated = [];
    protected array $created = [];

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

        $this->registerEvent();

        $this->extraValuesExist = $this->target
            ->extraValues()
            ->when($filters, function($q) use ($filters) { return $q->where($filters); })
            ->get();

        ExtraFieldService::getConstructFieldsByTypeInstance($this->extraFieldTypeInstance)
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
                ->when($filters, function($q) use ($filters) { return $q->where($filters); })
                ->withoutGlobalScopes()
                ->whereIn('id', $deleteValues->toArray())
                ->delete();
        }
        if ($this->updated || $this->created) {
            $this->target->fireExtraFieldUpdatedEvent();
        }

        return $this->target;
    }

    /**
     * @param ExtraField $field
     * @param boolean $isMultiple
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
                $values = Arr::get($this->dataInput, $field->parentInput);
                foreach ($values as $row => $groupValues) {

                    if ($this->extraFieldTypeEnumInstance::inputRequestHasFile($field->type)) {
                        $value = $this->target->handleSaveExtraValueIsFile(Arr::get($groupValues, $field->slug), $this->getCurrentValueInstance($field, $row));
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
            } elseif($this->extraFieldTypeEnumInstance::inputRequestIsMultiple($field->type)) {
                $values = Arr::get($this->dataInput, $field->inputName);
                foreach ($values as $row => $value) {
                    $dataSave = [
                        'extraFieldId' => $field->id,
                        'value' => $value,
                        'row' =>$row
                    ];
                    $this->storeValueToDatabase($field, $dataSave);
                }
            } else {

                if ($this->extraFieldTypeEnumInstance::inputRequestHasFile($field->type)
                    && $document = $this->target->handleSaveExtraValueIsFile(Arr::get($this->dataInput, $field->inputName), $this->getCurrentValueInstance($field))) {
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

    /**
     * @return Model|null
     */
    protected function getCurrentValueInstance($field, $row = null)
    {
        return $this->extraValuesExist
            ->when(!is_null($row), function(Collection $dt) use ($row){
                return $dt->where('row', $row);
            })
            ->firstWhere('extraFieldId', $field->id);
    }

    protected function storeValueToDatabase($field, $dataSave): void
    {
        /** @var Model $valueInstance */
        $valueInstance = $this->getCurrentValueInstance($field, $dataSave['row'] ?? null);

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

    protected function registerEvent()
    {
        $model = $this->target->extraValues()->getModel();
        $model::created(function($instance){
            $this->created[] = $instance->id;
        });
        $model::updated(function($instance){
            $this->updated[] = $instance->id;
        });
    }
}
