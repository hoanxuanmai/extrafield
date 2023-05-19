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
    protected array $treeSlug = [];
    protected ?int $currorRow = null;

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
                $this->appendDataSaveFromFieldInstance1($field);
                $this->treeSlug = [];
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
                        if ($this->extraFieldTypeEnumInstance::inputRequestIsMultiple($field->type)) {
                            $value = json_encode($value);
                        }
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

                    if ($this->extraFieldTypeEnumInstance::inputRequestHasFile($field->type)) {
                        $value = $this->target->handleSaveExtraValueIsFile($value, $this->getCurrentValueInstance($field, $row));
                    }  else {
                        $value = \HXM\ExtraField\ExtraField::getValueProcessionInstance($this->target->getMorphClass())->setValue($value, $field->type, $field);
                    }
                    $dataSave = [
                        'extraFieldId' => $field->id,
                        'value' => $value,
                        'row' =>$row
                    ];
                    $this->storeValueToDatabase($field, $dataSave);
                }
            } else {

                if ($this->extraFieldTypeEnumInstance::inputRequestHasFile($field->type)) {
                    $value = $this->target->handleSaveExtraValueIsFile(Arr::get($this->dataInput, $field->inputName), $this->getCurrentValueInstance($field));
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

    protected function appendDataSaveFromFieldInstance1(ExtraField $field): void
    {
        if ($this->extraFieldTypeEnumInstance::requireHasFields($field->type)) {
            $this->treeSlug[] = $field->slug;
            if ($this->extraFieldTypeEnumInstance::inputRequestIsMultiple($field->type)) {
                $tempTreeSlug = $this->treeSlug;
                foreach ($this->input($this->treeSlug) as $key => $value) {
                    $field->fields->each(function($childField) use ($tempTreeSlug, $key) {
                        $this->treeSlug = $tempTreeSlug;
                        $this->treeSlug[] = $key;
                        $this->currorRow = $key;
                        $this->appendDataSaveFromFieldInstance1($childField);
                    });
                }
            } else {
                $tempTreeSlug = $this->treeSlug;
                $field->fields->each(function($childField) use ($tempTreeSlug) {
                    $this->treeSlug = $tempTreeSlug;
                    $this->appendDataSaveFromFieldInstance1($childField);
                });
            }
            return;

        } else {
            if($this->extraFieldTypeEnumInstance::inputRequestIsMultiple($field->type)) {
                $this->treeSlug[] = $field->slug;
                $tempTreeSlug = $this->treeSlug;
                foreach ($this->input($this->treeSlug) as $row => $value) {
                    $this->treeSlug = $tempTreeSlug;
                    $this->treeSlug[] = $row;
                    $this->currorRow = $row;
                    $this->save($field);
                }
                return;
            } else {
                $this->treeSlug[] = $field->slug;
            }
        }
        $this->save($field);
    }

    protected function save(ExtraField $field)
    {
        if ($this->extraFieldTypeEnumInstance::inputRequestHasFile($field->type)) {
            $value = $this->target->handleSaveExtraValueIsFile($this->input($this->treeSlug), $this->getCurrentValueInstance($field));
        }  else {
            $value = \HXM\ExtraField\ExtraField::getValueProcessionInstance($this->target->getMorphClass())->setValue($this->input($this->treeSlug), $field->type, $field);
        }
        $dataSave = [
            'extraFieldId' => $field->id,
            'value' => $value,
            'row' => $this->currorRow,
            'slug' => $this->getTreeSlug()
        ];

        $this->storeValueToDatabase($field, $dataSave);
        $this->treeSlug = [];
        $this->currorRow = null;
    }

    protected function getTreeSlug(array $trees = null)
    {
        return implode('.', $trees ?? $this->treeSlug);
    }

    protected function input($key)
    {
        if (is_array($key)) {
            $key = $this->getTreeSlug($key);
        }
        return Arr::get($this->dataInput, $key);
    }
    /**
     * @return Model|null
     */
    protected function getCurrentValueInstance($field)
    {
        return $this->extraValuesExist
            ->where('slug', $this->getTreeSlug())
            ->firstWhere('extraFieldId', $field->id);
    }

    protected function storeValueToDatabase($field, $dataSave): void
    {
        /** @var Model $valueInstance */
        $valueInstance = $this->getCurrentValueInstance($field);

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
