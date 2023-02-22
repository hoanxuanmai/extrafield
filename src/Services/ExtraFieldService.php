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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ExtraFieldService
{
    static $useCache = true;
    protected static array $allSectionsByTarget = [];
    protected static array $allFieldsByTarget = [];


    static function getAllFieldsByTypeInstance(CanMakeExtraFieldInterface $hasExtraField): Collection
    {
        [$targetType, $targetId] = static::getTarget($hasExtraField);
        $key = $targetType.$targetId;
        if (! (static::$allFieldsByTarget[$key] ?? null)) {
            static::$allFieldsByTarget[$key] = static::getFromCache('allFieldsByTarget'.$key, function() use ($targetType, $targetId) {
                return static::buildQueryToGetList($targetType, $targetId)
                    ->whereFields()
                    ->get();
            });
        }
        return static::$allFieldsByTarget[$key];
    }

    static function getAllSectionsByTypeInstance(CanMakeExtraFieldInterface $hasExtraField): Collection
    {
        [$targetType, $targetId] = static::getTarget($hasExtraField);
        $key = $targetType.$targetId;

        if (! (static::$allSectionsByTarget[$key] ?? null)) {
            static::$allSectionsByTarget[$key] = static::getFromCache('allSectionsByTarget'.$key, function() use ($targetType, $targetId) {
                return static::buildQueryToGetList($targetType, $targetId)
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

    protected function buildQueryToGetList(string $targetType, ?int $targetId)
    {
        return ExtraField::where('target_type', $targetType)
            ->when(is_null($targetId), function($q) use ($targetId) {
                return $q->where('target_id', '<>', 0);
            }, function($q) use ($targetId) {
                return $q->where('target_id', $targetId);
            })
            ->with('fields.options', 'options');
    }
}
