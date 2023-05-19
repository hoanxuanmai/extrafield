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
use HXM\ExtraField\ExtraField;
use HXM\ExtraField\Models\ExtraField as ModelExtraField;
use Illuminate\Database\Eloquent\Relations\Relation;

trait HasExtraField
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

    function fields(): Relation
    {
        $targetType = $this->getExtraFieldTargetTypeInstance();
        $tables = ExtraField::getFieldAndOptionTables($targetType);
        /** @var ModelExtraField $instance */
        $instance = $this->newRelatedInstance(ExtraField::$modelField);
        $instance->setTable($tables['fields']);

        $foreignKey = 'target_id';

        $localKey = 'extraFieldTargetId';

        return $this->newHasMany(
            $instance->newQuery(), $targetType, $instance->getTable().'.'.$foreignKey, $localKey
        )->where('target_type', $targetType->getMorphClass());
    }
}
