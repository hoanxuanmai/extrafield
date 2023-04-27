<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Contracts;

use Illuminate\Database\Eloquent\Relations\Relation;

interface CanAccessExtraFieldValueInterface
{
    function extraValues(): Relation;

    function getExtraFieldTargetTypeInstance(): CanMakeExtraFieldInterface;

    /**
     * @param $file
     * @return string|null
     */
    function handleSaveExtraValueIsFile($file): ?string;

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
