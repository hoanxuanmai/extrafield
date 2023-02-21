<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField;

use Illuminate\Support\ServiceProvider;

class ExtraFiledServiceProvider extends ServiceProvider
{

    function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/extra_field.php' => config_path('extra_field.php')
        ], 'extra_field:config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations')
        ], 'extra_field:migration');
    }

    function register()
    {
        if (!ExtraField::$ignoreMigration)
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->mergeConfigFrom(__DIR__ . '/../config/extra_field.php', 'extra_field');
        $this->applyConfigEnumsInstance();
        $this->applyConfigValueProcessionsInstance();
    }

    function applyConfigEnumsInstance()
    {
        foreach (config('extra_field.enums', []) as $type => $instance) {
            ExtraField::setEnumInstance($type, $instance);
        }
    }

    function applyConfigValueProcessionsInstance()
    {
        foreach (config('extra_field.valueProcessions', []) as $type => $instance) {
            ExtraField::setValueProcessionInstance($type, $instance);
        }
    }
}
