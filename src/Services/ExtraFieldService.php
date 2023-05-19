<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Services;

use HXM\ExtraField\Contracts\CanMakeExtraFieldByInstanceInterface;
use HXM\ExtraField\Contracts\CanMakeExtraFieldInterface;
use HXM\ExtraField\Contracts\ExtraFieldTypeEnumInterface;
use HXM\ExtraField\Enums\ExtraFieldTypeEnums;
use HXM\ExtraField\Models\ExtraField;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class ExtraFieldService
{
    static $useCache = true;
    protected static array $allSectionsByTarget = [];
    protected static array $allFieldsByTarget = [];
    protected static array $constructFieldsByTarget = [];

    static function getConstructFieldsByTypeInstance(CanMakeExtraFieldInterface $hasExtraField): Collection
    {
        $hasExtraField = $hasExtraField->getExtraFieldTargetTypeInstance();
        [$targetType, $targetId] = static::getTarget($hasExtraField);
        $key = $targetType.$targetId;
        if (! (static::$constructFieldsByTarget[$key] ?? null)) {
            static::$constructFieldsByTarget[$key] = static::getFromCache('getConstructFieldsByTypeInstance'.$key, function() use ($hasExtraField, $targetType, $targetId) {
                return static::buildQueryToGetList($hasExtraField, $targetType, $targetId)
                    ->whereNull('parentId')
                    ->get();
            });
        }
        return static::$constructFieldsByTarget[$key];
    }

    static function buildValuesFromConstruct(CanMakeExtraFieldInterface $hasExtraField)
    {
        [$targetType] = static::getTarget($hasExtraField->getExtraFieldTargetTypeInstance());
        $instance = \HXM\ExtraField\ExtraField::getEnumInstance($targetType);
        return static::getConstructFieldsByTypeInstance($hasExtraField)
            ->groupBy('target_id')
            ->map(function($collect) use ($instance) {
                return $collect->mapWithkeys(function($field){
                    return $field->toDefault();
                });
            });
    }

    static function getAllFieldsByTypeInstance(CanMakeExtraFieldInterface $hasExtraField): Collection
    {
        $hasExtraField = $hasExtraField->getExtraFieldTargetTypeInstance();
        [$targetType, $targetId] = static::getTarget($hasExtraField);
        $key = $targetType.$targetId;

        if (! (static::$allFieldsByTarget[$key] ?? null)) {
            static::$allFieldsByTarget[$key] = static::getFromCache('allFieldsByTarget'.$key, function() use ($hasExtraField, $targetType, $targetId) {
                return static::buildQueryToGetList($hasExtraField, $targetType, $targetId)
                    ->whereFields()
                    ->get();
            });
        }
        return static::$allFieldsByTarget[$key];
    }

    static function getAllSectionsByTypeInstance(CanMakeExtraFieldInterface $hasExtraField): Collection
    {
        $hasExtraField = $hasExtraField->getExtraFieldTargetTypeInstance();
        [$targetType, $targetId] = static::getTarget($hasExtraField);
        $key = $targetType.$targetId;

        if (! (static::$allSectionsByTarget[$key] ?? null)) {
            static::$allSectionsByTarget[$key] = static::getFromCache('allSectionsByTarget'.$key, function() use ($hasExtraField, $targetType, $targetId) {
                return static::buildQueryToGetList($hasExtraField, $targetType, $targetId)
                    ->whereSections()
                    ->get();
            });
        }
        return static::$allSectionsByTarget[$key];
    }

    static function clearCache(CanMakeExtraFieldInterface $hasExtraField)
    {
        [$targetType, $targetId] = static::getTarget($hasExtraField);

        Cache::forget('allFieldsByTarget'.$targetType.$targetId);
        Cache::forget('allSectionsByTarget'.$targetType.$targetId);
        Cache::forget('getConstructFieldsByTypeInstance'.$targetType.$targetId);
        Cache::forget('buildValuesFromConstruct'.$targetType.$targetId);
    }

    protected function getTarget(CanMakeExtraFieldInterface $hasExtraField): array
    {
        $targetType = $hasExtraField->getMorphClass();
        $targetId = $hasExtraField instanceof CanMakeExtraFieldByInstanceInterface ? $hasExtraField->getKey() : 0;
        return [$targetType, $targetId];
    }

    protected static function getFromCache(string $key, \Closure $callback)
    {
        if (static::$useCache) {
            return Cache::remember($key, (int) config('extra_field.lifetime', 3600), $callback);
        }
        return $callback();
    }

    protected function buildQueryToGetList(CanMakeExtraFieldInterface $canMakeExtraField, string $targetType, ?int $targetId)
    {
        ExtraField::$loadMissingChildren = true;
        return $canMakeExtraField->fields()->getModel()->newQuery()->where('target_type', $targetType)
            ->when(is_null($targetId), function($q) use ($targetId) {
                return $q->where('target_id', '<>', 0);
            }, function($q) use ($targetId) {
                return $q->where('target_id', $targetId);
            });
    }
}
