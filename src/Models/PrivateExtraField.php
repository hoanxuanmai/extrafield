<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Models;

use HXM\ExtraField\Contracts\ExtraFieldTypeEnumInterface;
use HXM\ExtraField\Enums\ExtraFieldTypeEnums;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Contracts\Entities\Enums\ContractFieldTypeEnums;

class PrivateExtraField extends ExtraField
{

    function fields(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->newHasMany(
            $this->newQuery(), $this, $this->getTable().'.parentId', $this->getKeyName()
        );
    }

    function options(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        /** @var PrivateExtraFieldOption $instance */
        $instance = $this->newRelatedInstance(PrivateExtraFieldOption::class);
        $instance->setTable(Str::replace('_extra_fields', '_extra_field_options', $this->getTable()));

        $foreignKey = 'extraFieldId';

        return $this->newHasMany(
            $instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $this->getKeyName()
        );
    }
}
