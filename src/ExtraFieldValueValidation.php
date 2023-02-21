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
use HXM\ExtraField\Services\ExtraFieldService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ExtraFieldValueValidation
{
    protected CanMakeExtraFieldInterface $extraFieldTypeInstance;
    protected ExtraFieldTypeEnumInterface $extraFieldTypeEnumInstance;
    public string $errorBag;
    public array $dataInput;
    public $validated;

    /**
     * @param CanMakeExtraFieldInterface $extraFieldTypeInstance
     * @param array $data Data to validation
     * @param string $errorBag
     */
    public function __construct(CanMakeExtraFieldInterface $extraFieldTypeInstance, array $data, string $errorBag = 'default')
    {
        $this->extraFieldTypeInstance = $extraFieldTypeInstance->getExtraFieldTargetTypeInstance();
        $this->extraFieldTypeEnumInstance = \HXM\ExtraField\ExtraField::getEnumInstance(get_class($extraFieldTypeInstance));
        $this->dataInput = $data;
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
        $this->validated = $validator->validated();
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
        ExtraFieldService::getAllFieldsByTypeInstance($this->extraFieldTypeInstance)
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

    protected function addFieldRules(ExtraField $field, &$rules, &$resolved = [], $childOfArray = false): void
    {
        $type = $field->type;
        if (in_array($field->id, $resolved)) {
            return;
        } else {
            $resolved[] = $field->id;
        }
        $attribute = $childOfArray ? $field->parentInput . '.*.' . $field->slug : $field->inputName;

        if ($field->required) {
            $rules[$attribute] = ['required'];
        } else {
            $rules[$attribute] = ['sometimes'];
        }

        if ($this->extraFieldTypeEnumInstance::requireHasOptions($type)) {
            $rules[$attribute][] = 'in:'.$field->options->implode('id', ',');
        }

        if ($this->extraFieldTypeEnumInstance::inputRequestIsMultiple($type)) {
            $rules[$attribute][] = 'array';
            $rules[$attribute][] = 'min:1';
        }
        if ($this->extraFieldTypeEnumInstance::requireHasFields($type)) {
            $field->fields->each(function($childField) use (&$rules, $field, $childOfArray, &$resolved) {
                $this->addFieldRules($childField, $rules, $resolved, true);
            });
        }
        if ($this->extraFieldTypeEnumInstance instanceof ExtraFieldTypeEnumHasValidationInterface) {
            $rules[$attribute] = $this->extraFieldTypeEnumInstance::makeRuleByType($type, $rules[$attribute]);
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
