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

        $validator = Validator::make($this->dataInput, $this->getRulesByType(), [], $this->getAttributesByType());

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

    public function getRulesByType(&$rules = [])
    {
        $resolved = [];
        ExtraFieldService::getConstructFieldsByTypeInstance($this->extraFieldTypeInstance)
            ->each(function(ExtraField $field) use (&$rules, &$resolved){
                $this->addFieldRules($field, $rules, $resolved);
            });

        return $rules;
    }

    public function getAttributesByType(&$attributes = [])
    {
        $resolved = [];

        ExtraFieldService::getAllFieldsByTypeInstance($this->extraFieldTypeInstance)
            ->each(function(ExtraField $field) use (&$attributes, &$resolved){
                self::addFieldAttributes($field, $attributes, $resolved);
            });

        return $attributes;
    }

    /**
     * @param $key
     * @return array|\ArrayAccess|mixed
     */
    protected function input($key = null)
    {
        return Arr::get($this->dataInput, $key);
    }

    protected function addFieldRules(ExtraField $field, &$rules, &$resolved = [], $childOfArray = false): void
    {
        $type = $field->type;
        if (in_array($field->id, $resolved)) {
            return;
        } else {
            $resolved[] = $field->id;
        }
        $attribute = $childOfArray ? $field->parentInput . '.*.' . $field->slug : $field->inputName;

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
            $rules[$attribute] = [new RequiredIf(in_array($this->input($relatedField->inputName), $valueOptions))];
        } else {
            if ($field->required) {
                $rules[$attribute] = ['required'];
            } else {
                $rules[$attribute] = ['nullable'];
            }
        }

        if ($this->extraFieldTypeEnumInstance::requireHasOptions($type)) {
            $rules[$attribute][] = 'in:'.$field->options->implode('id', ',');
        }

        if ($this->extraFieldTypeEnumInstance::inputRequestIsMultiple($type)) {
            $rules[$attribute][] = 'array';
            $rules[$attribute][] = 'min:1';
        }
        if ($this->extraFieldTypeEnumInstance::requireHasFields($type)) {
            $field->fields->each(function($childField) use (&$rules, $field, $childOfArray, &$resolved, $type) {
                $this->addFieldRules($childField, $rules, $resolved, $this->extraFieldTypeEnumInstance::inputRequestIsMultiple($type));
            });
        }
        if ($this->extraFieldTypeEnumInstance instanceof ExtraFieldTypeEnumHasValidationInterface) {
            $rules[$attribute] = $this->extraFieldTypeEnumInstance::makeRuleByType($type, $rules[$attribute], $field);
        }
    }

    protected function addFieldAttributes(ExtraField $field, &$attributes, &$resolved = [], $childOfArray = false)
    {

        if (in_array($field->id, $resolved)) {
            return;
        } else {
            $resolved[] = $field->id;
        }

        $attributes[$childOfArray ? $field->parentInput . '.*.' . $field->slug : $field->inputName] = $field->label;

        if ($this->extraFieldTypeEnumInstance::requireHasFields($field->type)) {
            $field->fields->each(function($childField) use (&$attributes, $field, $childOfArray, &$resolved) {
                $this->addFieldAttributes($childField, $attributes, $resolved, true);
            });
        }
        return $attributes;
    }
}
