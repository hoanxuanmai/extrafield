<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PrivateExtraFieldOption extends Model
{
    protected $fillable = [
        'extraFieldId',
        'value',
        'label',
    ];

    function field(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PrivateExtraField::class, 'extraFieldId');
    }

    static function booted()
    {
        static::creating(function (self $model) {
            $slug = Str::slug($model->label, "_");
            if (is_numeric($slug)) {
                $slug = 'option_' . $slug;
            }
            $model->slug = $slug;
        });
    }
}
