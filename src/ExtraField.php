<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField;

use HXM\ExtraField\Contracts\ExtraFieldTypeEnumInterface;
use HXM\ExtraField\Contracts\ExtraValueProcessValueInterface;
use HXM\ExtraField\Enums\ExtraFieldTypeEnums;
use HXM\ExtraField\Services\ExtraValueProcessValue;

class ExtraField
{
    static bool $ignoreMigration = false;

    protected static array $enumInstances = [];
    protected static array $valueProcessionInstances = [];

    static function setEnumInstance(string $class, ExtraFieldTypeEnumInterface $instance):void
    {
        static::$enumInstances[$class] = $instance;
    }

    static function getEnumInstance(string $class = null): ExtraFieldTypeEnumInterface
    {
        $default = config('extra_field.defaults.enumInstance', new ExtraFieldTypeEnums());
        return is_null($class) ? $default : static::$enumInstances[$class] ?? $default;
    }

    static function setValueProcessionInstance(string $class, ExtraValueProcessValueInterface $instance):void
    {
        static::$enumInstances[$class] = $instance;
    }

    static function getValueProcessionInstance(string $class = null): ExtraValueProcessValueInterface
    {
        $default = config('extra_field.defaults.valueProcessionInstance', new ExtraValueProcessValue());
        return is_null($class) ? $default : static::$valueProcessionInstances[$class] ?? $default;
    }
}
