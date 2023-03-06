<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Traits;

use HXM\ExtraField\Contracts\CanMakeExtraFieldByInstanceInterface;
use HXM\ExtraField\Contracts\CanMakeExtraFieldInterface;
use HXM\ExtraField\Contracts\ExtraFieldTypeEnumInterface;
use HXM\ExtraField\Enums\ExtraFieldTypeEnums;
use HXM\ExtraField\Models\ExtraField;
use HXM\ExtraField\Models\PrivateExtraField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

trait HasPrivateExtraField
{

    function sections(): Relation
    {
        return $this->fields()->where('type', ExtraFieldTypeEnums::SECTION);
    }

    function getExtraFieldTargetIdAttribute(): int
    {
        return $this instanceof CanMakeExtraFieldByInstanceInterface ? ($this->getKey() ?? 0) : 0;
    }

    function getExtraFieldTargetTypeInstance(): CanMakeExtraFieldInterface
    {
        return $this;
    }


    public function fields(): Relation
    {
        $targetType = $this->getExtraFieldTargetTypeInstance();

        /** @var PrivateExtraField $instance */
        $instance = $this->newRelatedInstance(PrivateExtraField::class);
        $instance->setTable(Str::singular($targetType->getTable()).'_extra_fields');

        $foreignKey = 'target_id';

        $localKey = 'extraFieldTargetId';

        return $this->newHasMany(
            $instance->newQuery(), $targetType, $instance->getTable().'.'.$foreignKey, $localKey
        );
    }
    function getExtraFieldEnumsInstance(): ExtraFieldTypeEnumInterface
    {
        return new ExtraFieldTypeEnums();
    }
}
