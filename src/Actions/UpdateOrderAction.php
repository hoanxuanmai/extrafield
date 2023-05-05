<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Actions;

use HXM\ExtraField\Contracts\CanMakeExtraFieldInterface;
use HXM\ExtraField\Exceptions\CanNotMakeExtraFieldException;
use HXM\ExtraField\Services\ExtraFieldService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class UpdateOrderAction
{
    /**
     * @param Model $target
     * @param array $order
     * @return void
     * @throws CanNotMakeExtraFieldException
     */
    function handle(Model $target, array $order)
    {
        if (! $target instanceof CanMakeExtraFieldInterface) {
            throw new CanNotMakeExtraFieldException(get_class($target));
        }
        $targetInstance = $target->getExtraFieldTargetTypeInstance();

        $extrafields = $targetInstance->fields()->whereIn('id', array_keys($order))->get();

        $extrafields->each(function($field) use ($order) {
            $field->update(['order' => (int) ($order[$field->id] ?? 0) ]);
        });
        ExtraFieldService::clearCache($targetInstance);
    }
}
