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

trait HasExtraFieldByInstance
{

    function sections(): Relation
    {
        return $this->fields()->where('type', ExtraFieldTypeEnums::SECTION);
    }

    function getExtraFieldTargetTypeInstance(): CanMakeExtraFieldInterface
    {
        return $this;
    }

    function fields(): Relation
    {
        return $this->morphMany(ExtraField::class, 'target');
    }
}
