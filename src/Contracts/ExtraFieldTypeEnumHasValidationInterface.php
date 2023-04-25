<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Contracts;

use HXM\ExtraField\Models\ExtraField;

interface ExtraFieldTypeEnumHasValidationInterface
{
    static function makeRuleByType(string $type, array $rules = [], ExtraField $field): array;
}
