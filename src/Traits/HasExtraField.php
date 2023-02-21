<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Traits;

use HXM\ExtraField\Contracts\CanMakeExtraFieldInterface;
use HXM\ExtraField\Contracts\ExtraFieldTypeEnumInterface;
use HXM\ExtraField\Enums\ExtraFieldTypeEnums;
use HXM\ExtraField\Models\ExtraField;
use Illuminate\Database\Eloquent\Relations\Relation;

trait HasExtraField
{
    function sections(): Relation
    {
        return $this->fields()->where('type', ExtraFieldTypeEnums::SECTION);
    }

    function getExtraFieldTargetIdAttribute(): int
    {
        return 0;
    }

    function getExtraFieldTargetTypeInstance(): CanMakeExtraFieldInterface
    {
        return $this;
    }

    function fields(): Relation
    {
        return $this->getExtraFieldTargetTypeInstance()
            ->hasMany(ExtraField::class, 'target_id', 'extraFieldTargetId')
            ->where('target_type', $this->getMorphClass());
    }

    function getExtraFieldEnumsInstance(): ExtraFieldTypeEnumInterface
    {
        return new ExtraFieldTypeEnums();
    }
}
