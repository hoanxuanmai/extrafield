<p align="center">
    <a href="https://laravel.com"><img alt="Laravel 6.x/7.x/8.x" src="https://img.shields.io/badge/laravel-6.x/7.x/8.x-red.svg"></a>
    <a href="https://www.paypal.me/MaiXuanHoan"><img alt="Donate" src="https://img.shields.io/badge/Donate-%3C3-red"></a>
</p>

<h1 align="center"><a href="https://github.com/hoanxuanmai/extrafield">HoanXuanMai &mdash; hxm/extrafield</a></h1>



#### This is a respository developed to add extra fields to models on Laravel projects.The hxm/extrafield package provides a simple and flexible way to add custom fields to your Laravel Eloquent models. With this package, you can easily define additional fields to store any type of data that you need, such as metadata, settings, or preferences. The extra fields are seamlessly integrated with the model attributes, allowing you to access and modify them using the same syntax as regular attributes. Moreover, the package offers convenient features such as default values, validation rules, and automatic casting to various data types. Whether you are building a small application or a large-scale system, hxm/extrafield can help you extend your models with ease and efficiency.
#### Instead of you needing to create additional columns on the existing database structure, you can simply use this respository.



# Installation

Use [Composer] to install the package:

```
$ composer require hxm/extrafield
```
Use [Migration] to creating all tables:

```
$ php artisan migrate
```
you can also skip the migration by adding the following code in your `AppServiceProvider`.

```php

function boot()
{
    \HXM\ExtraField\ExtraField::$ignoreMigration = true;
}

```

## Basic Usage

To be able to add extra fields to the Model we just need to implement the Interfaces into the Model Class.
It comes with a built-in Trait so you can easily use it.

`HXM\ExtraField\Contracts\CanMakeExtraFieldInterface`
The Model Class will share the same Additional Fields across instances

You will have a Trait dedicated to it:
`HXM\ExtraField\Traits\HasExtraFieldByInstance`.


```php
use HXM\ExtraField\Contracts\CanMakeExtraFieldInterface;
use HXM\ExtraField\Traits\HasExtraField;

class AnyModel extends Model implements CanMakeExtraFieldInterface
{
    use HasExtraField;
}
```



`HXM\ExtraField\Contracts\CanMakeExtraFieldByInstanceInterface`: The Model class will have its own Extra Fields on each instance, to be used as an intermediate Model class.

You will have a Trait dedicated to it:
`HXM\ExtraField\Traits\HasExtraFieldByInstance`.

```php
use HXM\ExtraField\Contracts\CanMakeExtraFieldByInstanceInterface;
use HXM\ExtraField\Traits\HasExtraFieldByInstance;

class AnyModel extends Model implements CanMakeExtraFieldByInstanceInterface
{
    use HasExtraFieldByInstance;
}
```
`HXM\ExtraField\Contracts\CanMakeExtraFieldByInstanceInterface`: used when there is a parent Model class, contact the current class, to be able to get the Extra Fields option depending on the child Model class

```php
use HXM\ExtraField\Contracts\CanMakeExtraFieldInterface;
use HXM\ExtraField\Traits\HasExtraField;

class ParentModel extends Model implements CanMakeExtraFieldInterface
{   
    use HasExtraField;
    
    public function type()
    {
        return $this->belongsTo(AnyModel::class);
    }
    
    public function getExtraFieldTargetTypeInstance(): CanMakeExtraFieldInterface
    {
        return $this->type;
    }
}
```
do not forget to add function `public function getExtraFieldTargetTypeInstance(): CanMakeExtraFieldInterface`, it will help the system know which relation you are using to access Extra Fields

# Saving Extra Fields

Now, to add Extra Fields to the database, you just need to use actions directly on the controller in your admin

```php
...
use HXM\ExtraField\Actions\UpdateOrCreateFieldAction;
use Illuminate\Http\Request;
use AnyModel;

class Controller ... 
{
    function createOrUpdateExtraField(AnyModel $modelHasExtraField, Request $request, UpdateOrCreateFieldAction $action)
    {
        $action->handle($modelHasExtraField, $request, $allowMissingFields);
    }
}
```
Don't worry, because there is a built-in validation system before the data is saved to the database.


# Save Extra Field Values

`HXM\ExtraField\Contracts\CanAccessExtraFieldValueInterface` This contract stipulates on the Model class, where it can add values on the previously added list of Extra Fields

Attached to it are some pre-built Traits to ensure the structure: `HXM\ExtraField\Traits\HasExtraFieldValue`, `HXM\ExtraField\Traits\AutoValidationAndSaveExtraFieldValue`

```php
use HXM\ExtraField\Contracts\CanMakeExtraFieldByInstanceInterface;
use HXM\ExtraField\Traits\HasExtraFieldByInstance;
use HXM\ExtraField\Contracts\CanAccessExtraFieldValueInterface;
use HXM\ExtraField\Traits\HasExtraFieldValue;
use HXM\ExtraField\Traits\AutoValidationAndSaveExtraFieldValue;

class AnyModel extends Model implements CanMakeExtraFieldByInstanceInterface, CanAccessExtraFieldValueInterface
{
    use HasExtraFieldByInstance;
    use HasExtraFieldValue;
    // use AutoValidationAndSaveExtraFieldValue; 
    // Automatically save Extra Values when model saved
}
```
```php

use HXM\ExtraField\Contracts\CanMakeExtraFieldInterface;
use HXM\ExtraField\Contracts\CanAccessExtraFieldValueInterface;
use HXM\ExtraField\Traits\HasExtraFieldValue;
use HXM\ExtraField\Traits\AutoValidationAndSaveExtraFieldValue;

class ParentModel extends Model implements CanAccessExtraFieldValueInterface
{
    use HasExtraFieldValue;
    // use AutoValidationAndSaveExtraFieldValue; 
    // Automatically save Extra Values when model saved
    
    public function type()
    {
        return $this->belongsTo(AnyModel::class);
    }
    
    public function getExtraFieldTargetTypeInstance(): CanMakeExtraFieldInterface
    {
        return $this->type;
    }
}
```
`HasExtraFieldValue` there will be a 1-n relational function added to the Model, called `extraValues()`

`AutoValidationAndSaveExtraFieldValue` You use it so that the value is automatically saved for the Model class when you create or update it. Remember, it will come with a validation set before you can save the current Model. you may not use it, saving the value will be manually inserted another piece of code into your Controller


if you want to save it yourself, then remove the Trait from the Model class. the following code will help you to save manually by yourself:
```php
...
use HXM\ExtraField\ExtraFieldValueValidation;
use HXM\ExtraField\Actions\SaveExtraFieldValueForTargetAction;
use Illuminate\Http\Request;
use AnyModel;

class Controller ... 
{
    function store(Request $request, SaveExtraFieldValueForTargetAction $action)
    {
        $model = AnyModel::create([
                ...
            ]);
        $validation = new ExtraFieldValueValidation($model, $request->all());
        $action->handle($model, $validation);
    }
}
```

# Advanced

This repository is used with the <a href="https://packagist.org/packages/hxm/enum">hxm/enum</a> package. So you can create your own EnumInstances for many structures on your system.

first, don't forget to publish the package's config file via the command:
```
$ php artisan vendor:publish --tag=extra_field:config
```
```php
//extra_field.php
...
return [
    ...
    
    'enums' => [
        AnyModel::class => new \App\Enums\DemoExtraFieldTypeEnums(),
    ]
]
```
This Enum class must implement from Interface: `HXM\ExtraField\Contracts\ExtraFieldTypeEnumInterface`, otherwise you will get an error when integrating it into the system

```php
<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */
namespace App\Enums;

use HXM\Enum\Abstracts\EnumBase;
use HXM\ExtraField\Contracts\ExtraFieldTypeEnumInterface;
use HXM\ExtraField\Models\ExtraField;


class DemoExtraFieldTypeEnums extends EnumBase implements ExtraFieldTypeEnumInterface
{
    const SECTION = 'SECTION';
    const TEXT = 'TEXT';
    const NUMBER = 'NUMBER';
    const SELECT = 'SELECT';
    const MULTIPLE = 'MULTIPLE';
    ...

    protected static $descriptions = [
        'SECTION' => 'Section',
        'TEXT' => 'Text',
        'NUMBER' => 'Number',
        'SELECT' => 'Select',
        'MULTIPLE' => 'Multiple choice',
        ...
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
            default: return 'Text';

        }
    }

    static function requireHasOptions($value) : bool
    {
        return in_array($value, [self::SELECT, self::MULTIPLE]);
    }


    static function requireHasFields($value) : bool
    {
        return ...
    }

    static function inputRequestHasFile($value) : bool
    {
        return $value == self::FILE;
    }

    static function inputRequestIsMultiple($value) : bool
    {
        return ...
    }

    static function appendToArray(ExtraField $extraField): array
    {
        return [
            'component' => static::getComponentByType($extraField->type),
        ];
    }
}

```

In addition, you can also customize the value handler when saving and retrieving from the database
This Enum class must implement from Interface: `HXM\ExtraField\Contracts\ExtraValueProcessValueInterface`, otherwise you will get an error when integrating it into the system

```php
//extra_field.php
...
return [
    ...
    
    'valueProcessions' => [
        \App\Models\Demo::class => new \App\Casts\DemoExtraValueProcessValue(),
    ]
]
```
```php
<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace App\Casts;

use HXM\ExtraField\Contracts\ExtraValueProcessValueInterface;
use HXM\ExtraField\Models\ExtraField;
use HXM\ExtraField\Models\ExtraFieldValue;

class DemoExtraValueProcessValue implements ExtraValueProcessValueInterface
{

    function getValue($value, $valueType = null, ExtraFieldValue $model)
    {
        return $value;
    }

    function setValue($value, $valueType = null, ExtraField $model)
    {
     
        return $value;
    }
}

```

# License

Laravel User Agent is licensed under [The MIT License (MIT)](LICENSE).

# Donations
[Paypal](DONATIONS)

# Contacts
- hoanxuanmai@gmail.com
