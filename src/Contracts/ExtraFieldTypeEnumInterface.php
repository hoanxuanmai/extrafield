<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Contracts;

use HXM\ExtraField\Models\ExtraField;

interface ExtraFieldTypeEnumInterface
{
    static function getRule();

    static function buildSelectOptions();

    static function requireHasOptions($value): bool;

    static function requireHasFields($value): bool;

    static function inputRequestHasFile($value): bool;

    static function inputRequestIsMultiple($value): bool;

    static function appendToArray(ExtraField $extraField): array;
}
