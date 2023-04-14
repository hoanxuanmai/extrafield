<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Contracts;

use HXM\ExtraField\Models\ExtraField;
use HXM\ExtraField\Models\ExtraFieldValue;

interface ExtraValueProcessValueInterface
{
    function getValue($value, $valueType, ExtraFieldValue $extraFieldValue);

    function setValue($value, $valueType, ExtraField $extraField);
}
