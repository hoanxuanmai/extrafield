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

    public function __construct(array $attributes = [])
    {
        $this->table = \HXM\ExtraField\ExtraField::$tableOptions;
        parent::__construct($attributes);
    }

    function field(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\HXM\ExtraField\ExtraField::$modelField, 'extraFieldId');
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
