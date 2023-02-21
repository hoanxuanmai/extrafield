<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Services;

use HXM\ExtraField\Contracts\ExtraValueProcessValueInterface;
use HXM\ExtraField\Enums\ExtraFieldTypeEnums;
use HXM\ExtraField\Models\ExtraField;
use HXM\ExtraField\Models\ExtraFieldValue;
use Illuminate\Support\Facades\Date;

class ExtraValueProcessValue implements ExtraValueProcessValueInterface
{
    protected string $dateTimeSaveFormat = 'd/m/Y H:i';
    protected string $inputDateFormat = 'd/m/Y';
    protected string $inputTimeFormat = 'H:i';

    function getValue($value, $valueType = null, ExtraFieldValue $model)
    {
        switch ($valueType) {
            case ExtraFieldTypeEnums::DATETIME: {
                return empty($value) ? null : Date::createFromFormat($this->dateTimeSaveFormat, $value)->format($this->inputDateFormat .' '. $this->inputTimeFormat);
            }
            case ExtraFieldTypeEnums::DATE: {
                return empty($value) ? null : Date::createFromFormat($this->dateTimeSaveFormat, $value)->format($this->inputDateFormat);
            }
            case ExtraFieldTypeEnums::TIME: {
                return empty($value) ? null : Date::createFromFormat($this->dateTimeSaveFormat, $value)->format( $this->inputTimeFormat);
            }
            case ExtraFieldTypeEnums::NUMBER: {
                return (float) $value;
            }
            default: return (string) $value;
        }
    }

    function setValue($value, $valueType = null, ExtraField $model)
    {
        switch ($valueType) {
            case ExtraFieldTypeEnums::DATETIME: {
                $value = empty($value) ? $value : Date::createFromFormat($this->inputDateFormat .' '. $this->inputTimeFormat, $value)->format($this->dateTimeSaveFormat);
                break;
            }
            case ExtraFieldTypeEnums::TIME: {
                $value = empty($value) ? $value : Date::createFromFormat($this->inputTimeFormat, $value)->format($this->dateTimeSaveFormat);
                break;
            }
            case ExtraFieldTypeEnums::DATE: {
                $value = empty($value) ? $value : Date::createFromFormat($this->inputDateFormat, $value)->format($this->dateTimeSaveFormat);
                break;
            }
        }
        return $value;
    }
}
