<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use function Webmozart\Assert\Tests\StaticAnalysis\string;

class PrivateExtraFieldValue extends ExtraFieldValue
{
    public function options()
    {
        /** @var PrivateExtraFieldOption $instance */
        $instance = $this->newRelatedInstance(PrivateExtraFieldOption::class);
        $instance->setTable(Str::replace('_extra_field_values', '_extra_field_options', $this->getTable()));

        $foreignKey = 'extraFieldId';

        return $this->newHasMany(
            $instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, 'extraFieldId'
        );
        return $this->hasMany(PrivateExtraFieldOption::class, 'extraFieldId', 'extraFieldId');
    }

    static function booted()
    {
        static::addGlobalScope('joinField', function(Builder $query) {
            /** @var self $model */
            $model = $query->getModel();
            $table = $query->getModel()->getTable();
            return $query->with('options')->leftJoin(Str::replace('extra_field_values', 'extra_fields', $table).' as extra_field_values_join_field', $model->getTable().'.extraFieldId', 'extra_field_values_join_field.id')->select([
                $table.'.*',
                'extra_field_values_join_field.target_type as field_target_type',
                'extra_field_values_join_field.type',
                'extra_field_values_join_field.slug',
                'extra_field_values_join_field.parentInput',
                'extra_field_values_join_field.label',
                'extra_field_values_join_field.placeholder',
            ]);
        });

        static::creating(function($model){
            if (!$model->id) {
                $model->id = Str::uuid();
            }
        });
    }
}
