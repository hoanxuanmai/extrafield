<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Models;

use HXM\ExtraField\Contracts\ExtraFieldTypeEnumInterface;
use HXM\ExtraField\Enums\ExtraFieldTypeEnums;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Contracts\Entities\Enums\ContractFieldTypeEnums;

class ExtraField extends Model
{
    protected $fillable = [
        'target_type',
        'target_id',
        'parentId',
        'parentInput',
        'slug',
        'label',
        'placeholder',
        'type',
        'required',
        'order',
        'hidden',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'required' => 'boolean',
        'hidden' => 'boolean',
    ];

    public static $loadMissingChildren = false;

    function newCollection(array $models = [])
    {
        if (static::$loadMissingChildren) {
            $colection = parent::newCollection($models);
            return $colection->loadMissing(['fields.options', 'options']);
        }
        return parent::newCollection($models);
    }

    protected $appends =  ['inputName', 'title'];

    function target(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    function fields(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(static::class, 'parentId');
    }

    function options(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExtraFieldOption::class, 'extraFieldId');
    }

    function getInputNameAttribute()
    {
        return $this->parentInput ? $this->parentInput.'.'.$this->attributes['slug'] : $this->attributes['slug'];
    }

    function getTitleAttribute()
    {
        return $this->attributes['label'];
    }

    function scopeWhereSections($query)
    {
        return $query->where('type', ExtraFieldTypeEnums::SECTION);
    }

    function scopeWhereFields($query)
    {
        return $query->where('type','<>', ExtraFieldTypeEnums::SECTION);
    }

    public function toDefault()
    {
        $instance = \HXM\ExtraField\ExtraField::getEnumInstance($this->target_type);
        if ($instance::requireHasFields($this->type)) {
            return [$this->slug => $this->fields->mapWithKeys(function(self $model) {
                return [$model->slug =>$model->toDefault()];
            })];
        } else {
            return [$this->slug => null];
        }
    }

    public function toArray()
    {
        return array_merge(
            $this->attributesToArray(),
            $this->relationsToArray(),
            \HXM\ExtraField\ExtraField::getEnumInstance($this->attributes['target_type'] ?? null)::appendToArray($this)
        );
    }

    static function booted()
    {
        static::addGlobalScope('order', function(Builder $query) {
            $query->orderByDesc('order')->orderBy('id');
        });

        static::creating(function(self $model) {
            $model->target_id || $model->target_id = 0;
            $model->placeholder || $model->label = $model->label;
            if (!$model->slug){
                $slug = Str::slug($model->label, "_");
                if (is_numeric($slug)) {
                    $slug = 'field_' . $slug;
                }
                $model->slug = $slug;
            }
        });


        static::deleted(function (self $model) {
            if (!$model->fields || !$model->fields instanceof Collection) {
                $fields = $model->fields()->with('fields')->get();
            } else {
                $fields = $model->fields;
            }
            $fields->each(function($dt){
                $dt->delete();
            });
        });
    }
}
