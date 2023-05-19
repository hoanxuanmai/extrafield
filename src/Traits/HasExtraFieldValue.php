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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait HasExtraFieldValue
{
    static bool $withExtraValues = true;
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
        $tables = ExtraField::getPriviteTables(get_class($this));

        $tableValues = $tables['values'];
        $tableFields = $tables['fields'];
        /** @var ExtraFieldValue $instance */
        $instance = $this->newRelatedInstance(ExtraField::$modelValue);
        $instance->setTable($tableValues);
        $instance->setExtraFieldTable($tableFields);

        $foreignKey = 'target_id';

        return $this->newMorphMany($instance->newQuery(), $this, $tableValues.'.target_type', $tableValues.'.target_id', $this->getKeyName());
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
            if ($this->relations['extraValues'] ?? null && $this->relations['extraValues'] instanceof Collection && $this->relations['extraValues']->count()) {
                $this->relations['extraValues']->each(function (ExtraFieldValue $data) use($extraFieldEnumInstance) {
                    Arr::set(static::$cacheExtraValues[$this->getKey()], $data->slug, $data->value);
                });
            }
        }
        $wrap = config('extra_field.wrap', null);
        $this->attributes = array_merge(empty($wrap) ? static::$cacheExtraValues[$this->getKey()] : [$wrap => static::$cacheExtraValues[$this->getKey()]], $this->attributes);
        $this->syncOriginal();
        return $this;
    }

    function handleSaveExtraValueIsFile($file, $currentValue): ?string
    {
        if (empty($file)) {
            return null;
        }
        $path = 'extrafields/';
        if ($file instanceof UploadedFile) {
            $name = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();
            $size = $file->getSize();
            $path = $file->store($path . time(), 'public');
            $ext = pathinfo(storage_path($path, PATHINFO_EXTENSION));
            $clientExt = $file->getClientOriginalExtension();

            return json_encode([
                'ext' => $ext,
                'name' => Str::replaceLast('.'.$clientExt,'', $name).'.'.$ext,
                'path' => $path,
                'mime' => $mimeType,
                'size' => $size
            ]);
        } elseif ($currentValue && $value = $currentValue->value) {
            $value = $currentValue->value;
            $name = $file['name'] ?? $value['name'];
            $name = preg_replace('/\.\w+$/', '', $name).'.'.$value['ext'];
            $dataFile = array_merge($value, [
                'name' => $name
            ]);
        }

        return json_encode($file);
    }
}
