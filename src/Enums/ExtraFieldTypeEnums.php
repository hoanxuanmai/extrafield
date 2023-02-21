<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */
namespace HXM\ExtraField\Enums;

use HXM\Enum\Abstracts\EnumBase;
use HXM\ExtraField\Contracts\ExtraFieldTypeEnumInterface;
use HXM\ExtraField\Models\ExtraField;


class ExtraFieldTypeEnums extends EnumBase implements ExtraFieldTypeEnumInterface
{
    const SECTION = 'SECTION';
    const TEXT = 'TEXT';
    const NUMBER = 'NUMBER';
    const SELECT = 'SELECT';
    const MULTIPLE = 'MULTIPLE';
    const FILE = 'FILE';
    const CURRENCY = 'CURRENCY';
    const DATE = 'DATE';
    const TIME = 'TIME';
    const DATETIME = 'DATETIME';
    const CLAIMS = 'CLAIMS';
    const REPEATER = 'REPEATER';

    protected static $descriptions = [
        'SECTION' => 'Section',
        'TEXT' => 'Text',
        'NUMBER' => 'Number',
        'SELECT' => 'Select',
        'MULTIPLE' => 'Multiple choice',
        'FILE' => 'Upload File',
        'CURRENCY' => 'Currency',
        'DATE' => 'Only Date',
        'TIME' => 'Only Time',
        'DATETIME' => 'Date and Time',
        'CLAIMS' => 'Claims',
        'INSURANCE_COMPANY_SELECTOR' => 'Insurance Company',
        'REPEATER' => 'Repeater',
    ];

    static function buildSelectOptions(): \Illuminate\Support\Collection
    {
        return static::getCollection(function($des, $value) {
            return [
                'component' => self::getComponentByType($value),
                'requireHasOptions'=> self::requireHasOptions($value),
                'requireHasFields'=> self::requireHasFields($value),
                'masterSelect'=> $value != self::SECTION,
                'childSelect'=> ! in_array($value, [self::SECTION, self::CLAIMS, self::REPEATER]),
            ];
        });
    }

    static function getComponentByType($type): string
    {
        switch ($type) {
            case self::SELECT:
            case self::MULTIPLE: return 'Select';
            case self::FILE: return 'File';
            case self::DATE:  return 'Datepicker';
            case self::DATETIME: return 'DateTime';
            case self::TIME: return 'Time';
            case self::CURRENCY: return 'Currency';
            case self::CLAIMS: return 'Claims';
            case self::REPEATER: return 'Repeater';
            default: return 'Text';

        }
    }

    static function requireHasOptions($value) : bool
    {
        return in_array($value, [self::SELECT, self::MULTIPLE]);
    }


    static function requireHasFields($value) : bool
    {
        return in_array($value, [self::CLAIMS, self::REPEATER]);
    }

    static function inputRequestHasFile($value) : bool
    {
        return $value == self::FILE;
    }

    static function inputRequestIsMultiple($value) : bool
    {
        return $value == self::REPEATER;
    }

    static function appendToArray(ExtraField $extraField): array
    {
        return [
            'component' => static::getComponentByType($extraField->type),
        ];
    }
}
