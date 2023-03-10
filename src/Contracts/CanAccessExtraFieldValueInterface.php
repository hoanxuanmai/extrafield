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
}
