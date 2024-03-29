<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Services;

use Dompdf\Exception;
use HXM\ExtraField\Contracts\ExtraValueProcessValueInterface;
use HXM\ExtraField\ExtraField;
use HXM\ExtraField\Enums\ExtraFieldTypeEnums;
use HXM\ExtraField\Models\ExtraField as ExtraFieldModel;
use HXM\ExtraField\Models\ExtraFieldValue;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;

class ExtraValueProcessValue implements ExtraValueProcessValueInterface
{
    function getValue($value, $valueType, ExtraFieldValue $model)
    {
        switch ($valueType) {
            case ExtraFieldTypeEnums::DATETIME: {
                return empty($value) ? null : Date::createFromFormat(ExtraField::$dateTimeSaveFormat, $value)->format(ExtraField::$inputDateFormat .' '. ExtraField::$inputTimeFormat);
            }
            case ExtraFieldTypeEnums::DATE: {
                return empty($value) ? null : Date::createFromFormat(ExtraField::$dateTimeSaveFormat, $value)->format(ExtraField::$inputDateFormat);
            }
            case ExtraFieldTypeEnums::TIME: {
                return empty($value) ? null : Date::createFromFormat(ExtraField::$dateTimeSaveFormat, $value)->format( ExtraField::$inputTimeFormat);
            }
            case ExtraFieldTypeEnums::NUMBER: {
                return (float) $value;
            }
            case ExtraFieldTypeEnums::FILE: {
                if (is_null($value)) {
                    return $value;
                }
                $decode = json_decode($value, true);

                if (is_null($decode) && is_string($value) && Storage::exists($value)) {
                    $info = pathinfo(storage_path($value));
                    $decode = [
                        'ext' => $info['extension'],
                        'name' => $info['basename'],
                        'path' => $value,
                    ];
                }
                try {
                    $decode['url'] = tenant_asset($decode['path']);
                } catch (\Exception $e) {
                    return null;
                }
                return $decode;
            }
            default: return (string) $value;
        }
    }

    function setValue($value, $valueType, ExtraFieldModel $model)
    {
        switch ($valueType) {
            case ExtraFieldTypeEnums::DATETIME: {
                $value = empty($value) ? $value : Date::createFromFormat(ExtraField::$inputDateFormat .' '. ExtraField::$inputTimeFormat, $value)->format(ExtraField::$dateTimeSaveFormat);
                break;
            }
            case ExtraFieldTypeEnums::TIME: {
                $value = empty($value) ? $value : Date::createFromFormat(ExtraField::$inputTimeFormat, $value)->format(ExtraField::$dateTimeSaveFormat);
                break;
            }
            case ExtraFieldTypeEnums::DATE: {
                $value = empty($value) ? $value : Date::createFromFormat(ExtraField::$inputDateFormat, $value)->format(ExtraField::$dateTimeSaveFormat);
                break;
            }
        }

        return $value;
    }
}
