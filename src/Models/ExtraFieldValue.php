<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Models;

use HXM\ExtraField\ExtraField;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExtraFieldValue extends Model
{
    protected $extraFieldTable;

    public $timestamps = false;

    protected $fillable = [ 'target_type', 'target_id', 'extraFieldId', 'slug', 'value', 'row'];

    protected $keyType = 'string';

    public function __construct(array $attributes = [])
    {
        $this->table = \HXM\ExtraField\ExtraField::$tableValues;
        parent::__construct($attributes);
    }

    /**
     * Set $extraFieldTable
     * @param string $table
     * @return $this
     */
    function setExtraFieldTable(string $table)
    {
        $this->extraFieldTable = $table;
        return $this;
    }

    /**
     * get extraFieldTable
     * @return mixed
     */
    public function getExtraFieldTable()
    {
        if (is_null($this->extraFieldTable)) {
            $this->extraFieldTable = Str::replace(ExtraField::$tableValues, ExtraField::$tableFields, $this->getTable());
        }
        return $this->extraFieldTable;
    }

    /**
     * @param $attributes
     * @param $exists
     * @return ExtraFieldValue
     */
    public function newInstance($attributes = [], $exists = false)
    {
        return parent::newInstance($attributes, $exists)->setExtraFieldTable($this->getExtraFieldTable());
    }

    /**
     * @return string
     */
    public function getInputNameAttribute()
    {
        return $this->attributes['parentInput'] ?? null
            ? $this->attributes['parentInput'].'.'. $this->attributes['slug']
            : $this->attributes['slug'];
    }

    public function options()
    {
        $table = Str::replace(ExtraField::$tableFields, ExtraField::$tableOptions, $this->getExtraFieldTable());
        /** @var ExtraFieldOption $instance */
        $instance = $this->newRelatedInstance(ExtraField::$modelOption);
        $instance->setTable($table);

        $foreignKey = 'extraFieldId';

        return $this->newHasMany(
            $instance->newQuery(), $this, $table.'.'.$foreignKey, 'extraFieldId'
        );
    }

    function getValueAttribute()
    {
        return \HXM\ExtraField\ExtraField::getValueProcessionInstance($this->attributes['field_target_type'] ?? null)
            ->getValue($this->attributes['value'] ?? null, $this->attributes['type'] ?? null, $this);
    }

    static function booted()
    {
        static::addGlobalScope('joinField', function(Builder $query) {
            $model = $query->getModel();
            $tableFields = $model->getExtraFieldTable();
            $tableValues = $model->getTable();
            return $query->with('options')
                ->leftJoin($tableFields.' as extra_field_values_join_field', $tableValues.'.extraFieldId', 'extra_field_values_join_field.id')
                ->whereNotNull('extra_field_values_join_field.id')
                ->select([
                    $tableValues.'.*',
                    'extra_field_values_join_field.id as field_id',
                    'extra_field_values_join_field.target_type as field_target_type',
                    'extra_field_values_join_field.type',
                    'extra_field_values_join_field.slug as field_slug',
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
