<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExtraFieldOption extends Model
{
    protected $fillable = [
        'extraFieldId',
        'value',
        'label',
    ];

    function field(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ExtraField::class, 'extraFieldId');
    }

    static function booted()
    {
        static::creating(function (self $model) {if (is_numeric($model->label))
            $model->slug || $model->slug = is_numeric($model->label) ? 'option_' . Str::slug($model->label, "_") : Str::slug($model->label, "_");
        });
    }
}
