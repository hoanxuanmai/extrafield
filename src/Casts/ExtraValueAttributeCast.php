<?php

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Date;
use Modules\Contracts\Entities\Enums\ContractFieldTypeEnums;

/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

class ExtraValueAttributeCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes)
    {
        $valueType = $this->attributes['type'] ?? null;
        $value = $this->attributes['value'] ?? null;
        switch ($valueType) {
            case ContractFieldTypeEnums::DATE: {
                return empty($value) ? null : Date::createFromFormat($this->dateFormat, $value)->format('d/m/Y');
            }
//            case ContractFieldTypeEnums::DATETIME: {
//                return empty($value) ? null : Date::createFromFormat($this->dateFormat, $value)->format('d/m/Y');
//            }
            case ContractFieldTypeEnums::TIME: {
                return empty($value) ? null : Date::createFromFormat($this->dateFormat, $value)->format('h:i');
            }
            case ContractFieldTypeEnums::NUMBER: {
                return $this->fromFloat($value);
            }
            default: return (string) $value;
        }
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes)
    {
        return $value;
    }
}
