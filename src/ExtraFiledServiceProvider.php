<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField;

use HXM\ExtraField\Console\ExtraFieldInstallCommand;
use HXM\ExtraField\Services\ExtraFieldService;
use Illuminate\Support\ServiceProvider;

class ExtraFiledServiceProvider extends ServiceProvider
{

    function boot()
    {
        $config = config('extra_field');

        $this->publishes([
            __DIR__ . '/../config/extra_field.php' => config_path('extra_field.php')
        ], 'extra_field:config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations')
        ], 'extra_field:migration');

        if (! $config['cache'] ?? true) {
            ExtraFieldService::$useCache = false;
        }
        if (!ExtraField::$ignoreMigration) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        $tables = $config['tables'] ?? [];

        !empty($tables['values']) && ExtraField::$tableValues = $tables['values'];
        !empty($tables['fields']) && ExtraField::$tableFields = $tables['fields'];
        !empty($tables['options']) && ExtraField::$tableOptions = $tables['options'];

        $this->commands([ExtraFieldInstallCommand::class]);
    }

    function register()
    {

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
