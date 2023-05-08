<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

interface CanAccessExtraFieldValueInterface
{
    function extraValues(): Relation;

    function getExtraFieldTargetTypeInstance(): CanMakeExtraFieldInterface;

    /**
     * @param $file
     * @param Model|null $currentValue
     * @return string|null
     */
    function handleSaveExtraValueIsFile($file, $currentValue): ?string;

    /**
     * Register a extraFieldUpdated model event with the dispatcher.
     *
     * @param  \Illuminate\Events\QueuedClosure|\Closure|string  $callback
     * @return void
     */
    public static function extraFieldUpdated($callback);

    /**
     * Fire ExtraFieldUpdated event for the model.
     * @return mixed
     */
    function fireExtraFieldUpdatedEvent();
}
