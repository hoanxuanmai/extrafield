<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Contracts;

use Illuminate\Database\Eloquent\Relations\Relation;

interface CanMakeExtraFieldInterface
{
    /**
     * Get the class name for polymorphic relations.
     */
    public function getMorphClass();

    /**
     * Get the value of the model's primary key.
     */
    public function getKey();
    function fields(): Relation;
    function getExtraFieldTargetTypeInstance(): CanMakeExtraFieldInterface;
}
