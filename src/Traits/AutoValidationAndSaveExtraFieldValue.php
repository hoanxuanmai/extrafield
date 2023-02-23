<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Traits;

use HXM\ExtraField\Actions\SaveExtraFieldValueForTargetAction;
use HXM\ExtraField\Contracts\CanAccessExtraFieldValueInterface;
use HXM\ExtraField\ExtraFieldValueValidation;

trait AutoValidationAndSaveExtraFieldValue
{
    static function bootAutoValidationAndSaveExtraFieldValue()
    {
        static::saving(function(CanAccessExtraFieldValueInterface $model) {
            $validator = new ExtraFieldValueValidation($model->getExtraFieldTargetTypeInstance(), request()->all());
            $validator->validate();
            app()->bind(static::class.'ExtraFieldValueValidation', function() use ($validator){
                return $validator;
            });
        });
        static::saved(function(CanAccessExtraFieldValueInterface $model) {
            $service = new SaveExtraFieldValueForTargetAction($model);
            $service->handle(app(static::class.'ExtraFieldValueValidation'));
        });
    }
}
