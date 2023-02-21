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

class ExtraFieldService
{
    protected static array $allSectionsByTarget = [];
    protected static array $allFieldsByTarget = [];


    static function getAllFieldsByTypeInstance(CanMakeExtraFieldInterface $hasExtraField): Collection
    {
        $targetType = $hasExtraField->getMorphClass();
        $targetId = $hasExtraField instanceof CanMakeExtraFieldByInstanceInterface ? $hasExtraField->getKey() : 0;

        $key = $targetType. $targetId;

        if (! (static::$allFieldsByTarget[$key] ?? null)) {
            static::$allFieldsByTarget[$key] = static::buildQueryToGetList($hasExtraField)
                ->whereFields()
                ->get();
        }
        return static::$allFieldsByTarget[$key];
    }

    static function getAllSections(CanMakeExtraFieldInterface $hasExtraField): Collection
    {
        $targetType = $hasExtraField->getMorphClass();
        $targetId = $hasExtraField instanceof CanMakeExtraFieldByInstanceInterface ? $hasExtraField->getKey() : 0;

        $key = $targetType. $targetId;

        if (! (static::$allSectionsByTarget[$key] ?? null)) {
            static::$allSectionsByTarget[$key] = static::buildQueryToGetList($hasExtraField)
                ->whereSections()
                ->get();
        }
        return static::$allSectionsByTarget[$key];
    }

    protected function buildQueryToGetList(CanMakeExtraFieldInterface $hasExtraField)
    {
        $targetType = $hasExtraField->getMorphClass();
        $targetId = $hasExtraField instanceof CanMakeExtraFieldByInstanceInterface ? $hasExtraField->getKey() : 0;

        return ExtraField::where('target_type', $targetType)
            ->when(is_null($targetId), function($q) use ($targetId) {
                return $q->where('target_id', '<>', 0);
            }, function($q) use ($targetId) {
                return $q->where('target_id', $targetId);
            })
            ->with('fields.options', 'options');
    }
}
