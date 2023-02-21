<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExtraFieldValue extends Model
{
    public $timestamps = false;
    protected $fillable = [ 'target_type', 'target_id', 'extraFieldId', 'value', 'row'];
    protected $keyType = 'string';

    public function getInputNameAttribute()
    {
        return $this->attributes['parentInput'] ?? null
            ? $this->attributes['parentInput'].'.'. $this->attributes['slug']
            : $this->attributes['slug'];
    }
    public function options()
    {
        return $this->hasMany(ExtraFieldOption::class, 'extraFieldId', 'extraFieldId');
    }

    function getValueAttribute()
    {
        return \HXM\ExtraField\ExtraField::getValueProcessionInstance($this->attributes['target_type'] ?? null)
            ->getValue($this->attributes['value'] ?? null, $this->attributes['type'] ?? null, $this);
    }

    static function booted()
    {
        static::addGlobalScope('joinField', function(Builder $query) {
            return $query->with('options')->leftJoin(config('extra_field.tables.fields').' as extra_field_values_join_field', config('extra_field.tables.values').'.extraFieldId', 'extra_field_values_join_field.id')->select([
                config('extra_field.tables.values').'.*',
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
