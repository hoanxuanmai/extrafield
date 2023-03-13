<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Traits;

use HXM\ExtraField\Contracts\CanMakeExtraFieldInterface;
use HXM\ExtraField\Contracts\ExtraFieldTypeEnumInterface;
use HXM\ExtraField\Enums\ExtraFieldTypeEnums;
use HXM\ExtraField\Models\ExtraFieldValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;

trait HasExtraFieldValue
{
    static protected bool $withExtraValues = true;
    protected static array $cacheExtraValues = [];

    static function bootHasExtraFieldValue()
    {
        if (static::$withExtraValues) {
            static::addGlobalScope('withExtraValues', function(Builder $query){
                return $query->with('extraValues.options');
            });
        }
//
//        static::saving(function(self $model) {
//            if (static::$cacheExtraValues[$model->getKey()]) {
//                $keys = empty(config('extra_field.wrap', null)) ? array_keys(static::$cacheExtraValues[$model->getKey()]) : config('extra_field.wrap');
//
//                foreach ($keys as $key) {
//                    unset($model->attributes[$key]);
//                }
//                $model->syncOriginal();
//            }
//        });
    }
    function extraValues(): Relation
    {
        return $this->morphMany(ExtraFieldValue::class, 'target');
    }

    function getExtraFieldTargetTypeInstance(): CanMakeExtraFieldInterface
    {
        return $this;
    }

    function getExtraFieldEnumsInstance(): ExtraFieldTypeEnumInterface
    {
        return new ExtraFieldTypeEnums();
    }

    public function newCollection(array $models = [])
    {
        return (new Collection($models))->map(function(self $model) {
            if ($model->relationLoaded('extraValues')) {
                return $model->makeExtraFieldValueAttributes();
            }
            return $model;
        });
    }

    function makeExtraFieldValueAttributes()
    {
        if (!isset(static::$cacheExtraValues[$this->getKey()])) {
            static::$cacheExtraValues[$this->getKey()] = [];
            $dataList = [];
            if ($this->relations['extraValues'] ?? null && $this->relations['extraValues'] instanceof Collection && $this->relations['extraValues']->count()) {

                $this->relations['extraValues']->each(function (ExtraFieldValue $data) use(&$dataList) {
                    if (!is_null($data->row)) {
                        $dataList[] = $data->parentInput;
                        Arr::set(static::$cacheExtraValues[$this->getKey()], "$data->parentInput.$data->row.$data->slug", $data->value);
                    } else {
                        Arr::set(static::$cacheExtraValues[$this->getKey()], $data->inputName, $data->value);
                    }
                });
            }
            foreach (array_unique($dataList) as $key) {
                $newValue = collect(Arr::get(static::$cacheExtraValues[$this->getKey()], $key))->values()->toArray();
                Arr::set(static::$cacheExtraValues[$this->getKey()], $key, $newValue);
            }
        }
        $wrap = config('extra_field.wrap', null);
        $this->attributes = array_merge(empty($wrap) ? static::$cacheExtraValues[$this->getKey()] : [$wrap => static::$cacheExtraValues[$this->getKey()]], $this->attributes);
        $this->syncOriginal();
        return $this;
    }
}
