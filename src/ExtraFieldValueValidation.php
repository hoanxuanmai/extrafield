<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField;

use HXM\ExtraField\Contracts\CanMakeExtraFieldInterface;
use HXM\ExtraField\Contracts\ExtraFieldTypeEnumHasValidationInterface;
use HXM\ExtraField\Contracts\ExtraFieldTypeEnumInterface;
use HXM\ExtraField\Models\ExtraField;
use HXM\ExtraField\Models\ExtraFieldOption;
use HXM\ExtraField\Services\ExtraFieldService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\RequiredIf;
use Illuminate\Validation\ValidationException;

class ExtraFieldValueValidation
{
    protected CanMakeExtraFieldInterface $extraFieldTypeInstance;
    protected ExtraFieldTypeEnumInterface $extraFieldTypeEnumInstance;
    public string $errorBag;
    public array $dataInput;
    public $validated;
    public array $except = [];
    protected array $attributes = [];
    protected array $rules = [];
    protected array $resolved = [];


    /**
     * @param CanMakeExtraFieldInterface $extraFieldTypeInstance
     * @param array $data Data to validation
     * @param string $errorBag
     */
    public function __construct(CanMakeExtraFieldInterface $extraFieldTypeInstance, array $data, string $errorBag = 'default')
    {
        $this->extraFieldTypeInstance = $extraFieldTypeInstance->getExtraFieldTargetTypeInstance();
        $this->extraFieldTypeEnumInstance = \HXM\ExtraField\ExtraField::getEnumInstance(get_class($extraFieldTypeInstance));
        $this->dataInput = empty(config('extra_field.wrap')) ? $data : $data[config('extra_field.wrap')] ?? [];
        $this->errorBag = $errorBag;
    }

    /**
     * @throws ValidationException
     */
    function validate(): array
    {

        $validator = Validator::make($this->dataInput, $this->getRulesByType(), [], $this->attributes);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray())->errorBag($this->errorBag);
        }

        $this->validated = collect($validator->validated())->except($this->except)->toArray();

        return $this->validated;
    }

    /**
     * @throws ValidationException
     */
    function data(): array
    {
        if (is_null($this->validated)) {
            return $this->validate();
        }

        return $this->validated;
    }

    public function getRulesByType()
    {
        $this->rules = [];
        $this->attributes = [];
        ExtraFieldService::getConstructFieldsByTypeInstance($this->extraFieldTypeInstance)
            ->each(function(ExtraField $field) use (&$resolved){
                $this->addFieldRules($field, $resolved);
            });

        return $this->rules;
    }

    public function getAttributesByType(&$attributes = [])
    {

        return $this->attributes;
    }

    /**
     * @param $key
     * @return array|\ArrayAccess|mixed
     */
    protected function input($key = null)
    {
        return Arr::get($this->dataInput, $key);
    }

    protected function addFieldRules(ExtraField $field, $childOfArray = false): void
    {
        $type = $field->type;

        $attribute = $childOfArray ? $field->parentInput . '.*.' . $field->slug : $field->inputName;

        if (in_array($attribute, $this->resolved)) {
            return;
        }
        $this->resolved[] = $attribute;

        $settings = $field->settings;
        $requiredIfFieldId = $settings['requiredIfFieldId'] ?? null;
        $requiredIfOptionIds = $settings['requiredIfOptionIds'] ?? [];
        $hideIfNotRequired = $settings['hideIfNotRequired'] ?? false;

        /** @var ExtraField $relatedField */
        if ($requiredIfFieldId
            && $relatedField = ExtraFieldService::getAllFieldsByTypeInstance($this->extraFieldTypeInstance)->first(function(ExtraField $dt) use ($requiredIfFieldId) {
                return $dt->getKey() == $requiredIfFieldId;
            })) {
            $valueOptions = [];
            if ($requiredIfOptionIds) {
                $valueOptions = $relatedField->options->filter(function (ExtraFieldOption $dt) use ($requiredIfOptionIds) {
                    return in_array($dt->getKey(), $requiredIfOptionIds);
                })->pluck('id')->toArray();
            }
            if (!in_array($this->input($relatedField->inputName), $valueOptions) && $hideIfNotRequired) {
                $this->except[] = $field->inputName;
                return;
            }
            $this->rules[$attribute] = [new RequiredIf(in_array($this->input($relatedField->inputName), $valueOptions))];
        } else {
            if ($field->required) {
                $this->rules[$attribute] = ['required'];
            } else {
                $this->rules[$attribute] = ['nullable'];
            }
        }

        if ($this->extraFieldTypeEnumInstance::requireHasOptions($type)) {
            $this->rules[$attribute][] = 'in:'.$field->options->implode('id', ',');
        }

        if ($this->extraFieldTypeEnumInstance::inputRequestIsMultiple($type)) {
            $this->rules[$attribute][] = 'array';
            $childAttribute = $attribute.'.*';
            if ($field->required) {
                $this->rules[$childAttribute] = ['required'];
            } else {
                $this->rules[$childAttribute] = ['nullable'];
            }
            $this->attributes[$childAttribute] = $field->label;
        }
        if ($this->extraFieldTypeEnumInstance::requireHasFields($type)) {
            $field->fields->each(function($childField) use ($field, $childOfArray, $type) {
                $this->addFieldRules($childField, $this->extraFieldTypeEnumInstance::inputRequestIsMultiple($type));
            });
        }
        if ($this->extraFieldTypeEnumInstance instanceof ExtraFieldTypeEnumHasValidationInterface) {
            $this->rules[$attribute] = $this->extraFieldTypeEnumInstance::makeRuleByType($type, $this->rules[$attribute], $field);
        }
        $this->attributes[$attribute] = $field->label;
    }
}
