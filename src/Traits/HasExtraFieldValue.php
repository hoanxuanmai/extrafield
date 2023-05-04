<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Traits;

use HXM\ExtraField\Contracts\CanMakeExtraFieldInterface;
use HXM\ExtraField\Contracts\ExtraFieldTypeEnumInterface;
use HXM\ExtraField\Enums\ExtraFieldTypeEnums;
use HXM\ExtraField\ExtraField;
use HXM\ExtraField\Models\ExtraFieldValue;
use HXM\ExtraField\Services\ExtraFieldService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
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
    }

    /**
     * Register a extraFieldUpdated model event with the dispatcher.
     *
     * @param  \Illuminate\Events\QueuedClosure|\Closure|string  $callback
     * @return void
     */
    public static function extraFieldUpdated($callback)
    {
        static::registerModelEvent('extraFieldUpdated', $callback);
    }

    function fireExtraFieldUpdatedEvent()
    {
        $this->fireModelEvent('extraFieldUpdated', false);
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
        return ExtraField::getEnumInstance(get_class($this->getExtraFieldTargetTypeInstance()));
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
        $extraFieldEnumInstance = $this->getExtraFieldEnumsInstance();
        if (!isset(static::$cacheExtraValues[$this->getKey()])) {
            static::$cacheExtraValues[$this->getKey()] = [];
            $dataList = [];
            if ($this->relations['extraValues'] ?? null && $this->relations['extraValues'] instanceof Collection && $this->relations['extraValues']->count()) {
                $this->relations['extraValues']->each(function (ExtraFieldValue $data) use(&$dataList, $extraFieldEnumInstance) {
                    if (!is_null($data->row)) {
                        $key = "$data->parentInput.$data->row.$data->slug";
                        if ($extraFieldEnumInstance::inputRequestIsMultiple($data->type)) {
                            $key = "$data->inputName.$data->row";
                        }
                        Arr::set(static::$cacheExtraValues[$this->getKey()], $key, $data->value);
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
