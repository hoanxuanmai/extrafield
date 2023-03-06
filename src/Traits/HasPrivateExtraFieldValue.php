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
use HXM\ExtraField\Models\PrivateExtraField;
use HXM\ExtraField\Models\PrivateExtraFieldValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait HasPrivateExtraFieldValue
{
    static protected bool $withExtraValues = true;
    protected static array $cacheExtraValues = [];

    static function bootHasExtraFieldValue()
    {
        if (static::$withExtraValues) {
            static::addGlobalScope('withExtraValues', function(Builder $query){
                return $query->with('extraValues');
            });
        }
    }
    function extraValues(): Relation
    {
        $table = Str::singular($this->getExtraFieldTargetTypeInstance()->getTable()).'_extra_field_values';
        /** @var PrivateExtraFieldValue $instance */
        $instance = $this->newRelatedInstance(PrivateExtraFieldValue::class);
        $instance->setTable($table);

        $foreignKey = 'target_id';

        $localKey = 'extraFieldTargetId';
        return $this->newMorphMany($instance->newQuery(), $this, $table.'.target_type', $table.'.target_id', $this->getKeyName());
    }

    function getExtraFieldTargetTypeInstance(): CanMakeExtraFieldInterface
    {
        return $this;
    }

    function getExtraFieldEnumsInstance(): ExtraFieldTypeEnumInterface
    {
        return new ExtraFieldTypeEnums();
    }

    public function toArray()
    {
        if (!isset(static::$cacheExtraValues[$this->getKey()])) {
            static::$cacheExtraValues[$this->getKey()] = [];
            $dataList = [];
            if ($this->relations['extraValues'] ?? null && $this->relations['extraValues'] instanceof Collection && $this->relations['extraValues']->count()) {

                $this->relations['extraValues']->each(function (PrivateExtraFieldValue $data) use(&$dataList) {
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

        $modelValues = parent::toArray();
        $wrap = config('extra_field.wrap', null);
        return array_merge(empty($wrap) ? static::$cacheExtraValues[$this->getKey()] : [$wrap => static::$cacheExtraValues[$this->getKey()]], $modelValues);
    }
}
