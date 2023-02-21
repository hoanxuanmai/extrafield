<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Http\Requests;

use HXM\ExtraField\Enums\ExtraFieldTypeEnums;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class SaveOrUpdateExtraValue extends FormRequest
{
    protected $errorBag = 'SaveOrUpdateExtraValue';
    protected $parentInstance;
    protected $updateInstance;
    protected $cacheUniqueSlugs = [];

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
                (new Unique('extra_fields', 'slug'))
                    ->where('target_id', $this->target->id)
                    ->where('target_type', $this->target->getMorphClass())
                    ->where('parentId', $this->get('parentId', 0))
                    ->whereNot('id', $this->get('id', 0))
            ],
            'label' => 'required',
            'placeholder' => 'sometimes',
            'type' => ['required', ExtraFieldTypeEnums::getRule()],
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
            $rules['target_type'] = 'required';
            $rules['target_id'] = 'required';
        }

        if (ExtraFieldTypeEnums::requireHasOptions($this->get('type'))) {
            $rules = array_merge($rules, $this->buildRuleOptions());
        }

        if (ExtraFieldTypeEnums::requireHasFields($this->get('type'))) {
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
                    'required',
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
                'required',
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

    function prepareForValidation()
    {
        $ids = array_filter([$this->get('id'), $this->get('parentId')]);
        $preLoad = collect();
        if (count($ids)) {
            $preLoad = $this->target->fields()->whereIn('id', $ids)->get();
        }
        if ($this->get('id')) {
            $this->updateInstance = $preLoad->firstWhere('id', $this->get('id'));
        }
        if ($this->get('parentId')) {
            $this->parentInstance = $preLoad->firstWhere('id', $this->get('parentId'));
            $this->merge([
                'target_id' => $this->target->id,
                'target_type' => $this->target->getMorphClass()
            ]);
        } else {
            $this->merge([
                'parentId' => 0
            ]);
        }

        $this->merge([
            'slug' => $this->input('slug', Str::slug($this->input('label'), '_')),
            'placeholder' => $this->input('placeholder', $this->input('label')),
        ]);
    }

    function attributes()
    {
        return [
            'slug' => "Label",
            'label' => "Label",
            'type' => "Type",
            'options' => "Options",
            'options.*.label' => "Label",
            'fields.*.label' => "Label",
            'fields.*.type' => "Type",
            'fields.*.fields' => "Fields",
            'fields.*.options' => "Options",
        ];
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

    function passedValidation()
    {

    }
}
