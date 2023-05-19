<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField;

use HXM\ExtraField\Contracts\CanAccessExtraFieldValueInterface;
use HXM\ExtraField\Contracts\CanMakeExtraFieldInterface;
use HXM\ExtraField\Contracts\ExtraFieldTypeEnumInterface;
use HXM\ExtraField\Contracts\ExtraValueProcessValueInterface;
use HXM\ExtraField\Enums\ExtraFieldTypeEnums;
use HXM\ExtraField\Exceptions\CanNotMakeExtraFieldException;
use HXM\ExtraField\Services\ExtraValueProcessValue;
use Illuminate\Support\Str;

class ExtraField
{
    static string $dateTimeSaveFormat = 'd/m/Y H:i';
    static string $inputDateFormat = 'd/m/Y';
    static string $inputTimeFormat = 'H:i';

    static $tableFields = 'extra_fields';
    static $tableOptions = 'extra_field_options';
    static $tableValues = 'extra_field_values';

    static $modelField = \HXM\ExtraField\Models\ExtraField::class;
    static $modelOption = \HXM\ExtraField\Models\ExtraFieldOption::class;
    static $modelValue = \HXM\ExtraField\Models\ExtraFieldValue::class;

    static bool $ignoreMigration = false;

    protected static array $enumInstances = [];
    protected static array $valueProcessionInstances = [];

    protected static array $privateTables = [];

    static function getPriviteTables(string $target): array
    {
        if (! isset(static::$privateTables[$target])) {
            $instance = (new $target);
            if (! $instance instanceof CanAccessExtraFieldValueInterface) {
                throw new CanNotMakeExtraFieldException($target);
            }
            $pre = Str::singular($instance->getTable()).'_';
            $fieldOptionTables = self::getFieldAndOptionTables($instance->getExtraFieldTargetTypeInstance());
            $configs = config('extra_field.private_tables');
            if (in_array($target, $configs)) {
                static::$privateTables[$target] = array_merge(
                    [ 'values' => $pre.ExtraField::$tableValues ],
                    $fieldOptionTables
                );
            } elseif (isset($configs[$target])) {
                $pre = $configs[$target];
                static::$privateTables[$target] = array_merge(
                    [ 'values' => $pre.ExtraField::$tableValues],
                    $fieldOptionTables
                );
            } else {
                static::$privateTables[$target] = array_merge(
                    [ 'values' => ExtraField::$tableValues],
                    $fieldOptionTables
                );
            }
        }
        return static::$privateTables[$target];
    }

    static function getFieldAndOptionTables(CanMakeExtraFieldInterface $instance)
    {
        $pre = Str::singular($instance->getExtraFieldTargetTypeInstance()->getTable()).'_';
        $target = get_class($instance);
        $configs = config('extra_field.private_tables');
        if (in_array($target, $configs)) {
            return  [
                'fields' => $pre.ExtraField::$tableFields,
                'options' => $pre.ExtraField::$tableOptions,
            ];
        } elseif (isset($configs[$target])) {
            $pre = $configs[$target];
            return [
                'fields' => $pre.ExtraField::$tableFields,
                'options' => $pre.ExtraField::$tableOptions,
            ];
        }
        return [
            'fields' => ExtraField::$tableFields,
            'options' => ExtraField::$tableOptions,
        ];
    }

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
