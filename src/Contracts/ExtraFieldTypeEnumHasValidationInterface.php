<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Contracts;

interface ExtraFieldTypeEnumHasValidationInterface
{
    static function makeRuleByType(string $type, array $rules = []): array;
}
