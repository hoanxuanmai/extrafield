<?php
/**
 * Created by Vincent
 * @author vincent@pixodeo.net
 */

namespace HXM\ExtraField\Actions;

use HXM\ExtraField\Contracts\CanMakeExtraFieldByInstanceInterface;
use HXM\ExtraField\Contracts\CanMakeExtraFieldInterface;
use HXM\ExtraField\Contracts\ExtraFieldTypeEnumInterface;
use HXM\ExtraField\Exceptions\CanNotMakeExtraFieldException;
use HXM\ExtraField\ExtraField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationException;

class UpdateOrCreateFieldAction
{
    protected $parentInstance;
    protected $updateInstance;
    protected $cacheUniqueSlugs = [];
    protected CanMakeExtraFieldInterface $target;
    protected ExtraFieldTypeEnumInterface $enumInstance;
    protected Collection $data;

    function handle(Model $target, Request $request)
    {
        if (! $target instanceof CanMakeExtraFieldInterface) {
            throw new CanNotMakeExtraFieldException(get_class($target));
        }
        $this->data = collect($request->all());
        $this->target = $target;
        $this->enumInstance = ExtraField::getEnumInstance($target->getMorphClass());
        $this->merge([
            'target_id' =>  $target instanceof CanMakeExtraFieldByInstanceInterface ? $this->target->getKey() : 0,
            'target_type' => $this->target->getMorphClass()
        ]);
        $this->prepareForValidation();
        $validator = Validator::make($this->data->all(), $this->rules(), [], $this->attributes());
        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        } else {
            return $this->updateOrSave();
        }

    }

    protected function updateOrSave()
    {
        if ($this->isUpdateFunction()) {
            $fieldSetting = $this->getUpdateInstance();
            $fieldSetting->update($this->data->only(['name', 'label', 'placeholder', 'required', 'hidden', 'type', 'settings', 'parentInput'])->toArray());
        } else {
            $fieldSetting = $this->getParentInstance()
                ->fields()
                ->create($this->data->only(['name', 'target_type','target_id', 'label', 'placeholder', 'type', 'required', 'hidden', 'settings', 'parentInput'])->toArray());
        }

        if ($this->enumInstance::requireHasOptions($fieldSetting->type)) {
            $this->updateOptions($fieldSetting, $this->get('options'));
        } else {
            $fieldSetting->options()->delete();
        }
        if ($this->enumInstance::requireHasFields($fieldSetting->type)) {
            $this->updateFieldChildren($fieldSetting, $this->get('fields'));
        } else {
            $fieldSetting->fields()->delete();
        }
        return $fieldSetting;
    }

    protected function updateOptions($fieldSetting, $options)
    {
        $newOptions = collect($options);
        $updateIds = $newOptions->pluck('id')->filter()->toArray();

        $existOptions = $fieldSetting->options()->get();
        $updateOptions = $existOptions->filter(function($dt) use ($updateIds){
            return in_array($dt->id, $updateIds);
        });
        $deleteOptions = $existOptions->diff($updateOptions);
        if ($deleteOptions->count()) {
            $fieldSetting->options()->whereIn('id', $deleteOptions->pluck('id')->toArray())->delete();
        }
        foreach ($newOptions as $k => $data) {
            $exist = null;
            $data['value'] = $k + 1;
            if ($data['id'] ?? null)
                $exist = $updateOptions->firstWhere('id', $data['id']);
            if ($exist) {
                $exist->update($data);
            } else {
                $fieldSetting->options()->create($data);
            }
        }
    }
    protected function updateFieldChildren($fieldSetting, $fields)
    {
        $newFields = collect($fields);
        $updateIds = $newFields->pluck('id')->filter()->toArray();

        $existFields = $fieldSetting->fields()->get();
        $updateFields = $existFields->filter(function($dt) use ($updateIds){
            return in_array($dt->id, $updateIds);
        });
        $deleteFields = $existFields->diff($updateFields);
        if ($deleteFields->count()) {
            $fieldSetting->fields()->whereIn('id', $deleteFields->pluck('id')->toArray())->delete();
        }
        foreach ($newFields as $ind => $data) {
            $data['target_type'] = $this->get('target_type');
            $data['target_id'] = $this->get('target_id');
            $data['parentInput'] = $fieldSetting->inputName;
            $childField = null;
            if ($data['id'] ?? null)
                $childField = $updateFields->firstWhere('id', $data['id']);
            if ($childField) {
                $childField->update($data);
            } else {
                $childField = $fieldSetting->fields()->create($data);
            }
            if ($this->enumInstance::requireHasOptions($childField->type)) {
                $this->updateOptions($childField, $fields[$ind]['options']);
            } else {
                $childField->options()->delete();
            }
        }
    }

    function rules()
    {
        $rules =  [
            'id' => [
                'sometimes',
                function ($attribute, $value, $fail) {
                    if ($value && !$this->updateInstance) {
                        $fail('The '.$attribute.' is invalid.');
                    }
                }
            ],
            'slug' => [
                'required',
                (new Unique(config('extra_field.tables.fields'), 'slug'))
                    ->where('target_id', $this->get('target_id'))
                    ->where('target_type', $this->get('target_type'))
                    ->where('parentId', $this->get('parentId', 0))
                    ->whereNot('id', $this->get('id', 0))
            ],
            'label' => 'required|alpha_num',
            'placeholder' => 'sometimes',
            'type' => ['required', $this->enumInstance::getRule()],
            'required' => 'sometimes|boolean',
            'hidden' => 'sometimes|boolean',
            'settings' => 'sometimes|array'
        ];

        if ($this->get('parentId')) {
            $rules['parentId'] = function ($attribute, $value, $fail) {
                if (!$this->parentInstance) {
                    $fail('The '.$attribute.' is invalid.');
                }
            };
        }

        if ($this->enumInstance::requireHasOptions($this->get('type'))) {
            $rules = array_merge($rules, $this->buildRuleOptions());
        }

        if ($this->enumInstance::requireHasFields($this->get('type'))) {
            $rules = array_merge($rules, [
                'fields' => [
                    'required',
                    'array'
                ],
                'settings' => 'sometimes',
                'fields.*.id' => [
                    'sometimes',
                    function ($attribute, $value, $fail) {
                        if ($value && ! $this->get('id')) {
                            return $fail('The parent of '.$attribute.' must have a Id.');
                        }
                    },
                ],
                'fields.*.label' => [
                    'required', 'alpha_num',
                    function ($attribute, $value, $fail) {
                        $value = Str::slug($value, '_');
                        if (!isset($this->cacheUniqueSlugs['fields'])) {
                            $this->cacheUniqueSlugs['fields'] = [];
                        }
                        if (in_array($value, $this->cacheUniqueSlugs['fields'])) {
                            $fail('The '.$attribute.' exist in list.');
                        } else {
                            $this->cacheUniqueSlugs['fields'][] = $value;
                        }
                    }
                ],
                'fields.*.type' => 'required',
                'fields.*.hidden' => 'sometimes|boolean',
                'fields.*.required' => 'sometimes|boolean',
            ], $this->buildRuleOptions('fields.*.'));

        }
        return $rules;
    }

    protected function buildRuleOptions($preKey = ''): array
    {
        return [
            $preKey.'options' => [
                "required_if:".$preKey."type,SELECT,MULTIPLE",
                'array',
            ],
            $preKey."options.*.id" => [
                'sometimes',
                function ($attribute, $value, $fail) {
                    if ($value && ! $this->get('id')) {
                        return $fail('The parent of '.$attribute.' must have a Id.');
                    }
                },
            ],
            $preKey."options.*.label" => [
                'required', 'alpha_num',
                function ($attribute, $value, $fail) use($preKey) {
                    $value = Str::slug($value, '_');
                    if (!isset($this->cacheUniqueSlugs[$preKey.'options'])) {
                        $this->cacheUniqueSlugs[$preKey.'options'] = [];
                    }
                    if (in_array($value, $this->cacheUniqueSlugs[$preKey.'options'])) {
                        $fail('The '.$attribute.' exist in list.');
                    } else {
                        $this->cacheUniqueSlugs[$preKey.'options'][] = $value;
                    }
                }
            ]
        ];
    }

    function get(string $key, $default = null)
    {
        return Arr::get($this->data, $key, $default);
    }
    function merge($data)
    {
        $this->data = $this->data->merge($data);
    }

    function prepareForValidation()
    {
        $ids = array_filter([$this->get('id'), $this->get('parentId')]);
        $preLoad = collect();
        if (count($ids)) {
            $preLoad = $this->target
                ->fields()
                ->whereIn('id', $ids)->get();
        }
        if ($id = $this->get('id')) {
            $this->updateInstance = $preLoad->firstWhere('id', $id);
        }
        if ($parentId = $this->get('parentId')) {
            $this->parentInstance = $preLoad->firstWhere('id', $parentId);
            $this->merge([
                'parentInput' => $this->parentInstance->inputName ?? null
            ]);
        } else {
            $this->merge([
                'parentId' => 0
            ]);
        }

        $this->merge([
            'slug' => $this->get('slug', Str::slug($this->get('label'), '_')),
            'placeholder' => $this->get('placeholder', $this->get('label')),
        ]);
    }


    function attributes()
    {
        return config('extra_field.validations.attributes', []);
    }

    function getParentInstance(): Model
    {
        return $this->parentInstance ?: $this->target;
    }

    function getUpdateInstance(): ?Model
    {
        return $this->updateInstance;
    }

    function isUpdateFunction(): bool
    {
        return !!$this->updateInstance;
    }
}
