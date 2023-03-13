<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Console;

use HXM\ExtraField\Contracts\CanMakeExtraFieldInterface;
use HXM\ExtraField\Exceptions\CanNotMakeExtraFieldException;
use HXM\ExtraField\ExtraField;
use HXM\ExtraField\Models\Test;
use HXM\ExtraField\Services\ExtraFieldService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;

class ExtraFieldInstallCommand extends Command
{
    /**
     * @var MessageBag
     */
    private $errors;

    protected $signature = 'extrafield:install';

    public function __construct()
    {
        parent::__construct();
        $this->errors = new MessageBag();
    }

    function handle()
    {
        tenancy()->runForMultiple([], function(){
            $privates = config('extra_field.privates', []);
            $installs = [];
            foreach ($privates as $target => $tables) {
                if (is_string($tables)) {
                    $target = $tables;
                    $tables = [];
                }
                /** @var Model $targetInstance */
                $targetInstance = app()->make($target);
                if ($targetInstance instanceof CanMakeExtraFieldInterface) {
                    $prefix = Str::singular($targetInstance->getTable()).'_';
                    $installs[] = $this->checkInstalled($prefix, $tables);
                } else {
                    throw new CanNotMakeExtraFieldException($target);
                }
            }
            if ($this->errors->isNotEmpty()) {
                foreach ($this->errors->getMessages() as $message) {
                    $this->error(implode(', ', Arr::wrap($message)));
                }
                exit();
            }


            foreach ($installs as $tables) {
                $this->createTables($tables);
            }
        });
    }

    private function checkInstalled(string $prefix, array $tables)
    {
        $tables = array_merge([
            'fields' => 'extra_fields',
            'options' => 'extra_field_options',
            'values' => 'extra_field_values',
        ], $tables);

        return [
            'fields' => $this->checkHasFieldsTable($prefix.$tables['fields']) ?  null : $prefix.$tables['fields'],
            'options' => $this->checkHasOptionsTable($prefix.$tables['options']) ? null : $prefix.$tables['options'],
            'values' => $this->checkHasValuesTable($prefix.$tables['values']) ? null : $prefix.$tables['values'],
        ];
    }

    private function createTables($tables)
    {
        !empty($tables['fields']) && $this->createFieldsTable($tables['fields']);
        !empty($tables['options']) && $this->createOptionsTable($tables['options'], $tables['fields']);
        !empty($tables['values']) && $this->createValuesTable($tables['values'], $tables['fields']);
    }

    private function checkHasFieldsTable($table)
    {
        if (Schema::hasTable($table)) {
            if (!Schema::hasColumns($table, [
                'target_type',
                'target_id',
                'parentId',
                'parentInput',
                'slug',
                'label',
                'placeholder',
                'type',
                'required',
                'order',
                'hidden',
                'settings',
            ])) {
                $this->errors->add($table, "the $table was existed!");
            }
            return true;
        }
        return false;
    }
    private function createFieldsTable($tableName)
    {
        $this->info('creating '.$tableName);
        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->morphs('target');
            $table->bigInteger('parentId')->default(0);
            $table->string('parentInput')->nullable();
            $table->string('slug');
            $table->string('label');
            $table->string('placeholder')->nullable();
            $table->string('type');
            $table->boolean('required')->default(true);
            $table->boolean('hidden')->default(false);
            $table->tinyInteger('order')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['target_id', 'parentId', 'slug']);
        });
    }

    private function checkHasOptionsTable($table)
    {
        if (Schema::hasTable($table)) {
            if (!Schema::hasColumns($table, [
                'extraFieldId',
                'value',
                'label',
            ])) {
                $this->errors->add($table, "the $table was existed!");
            }
            return true;
        }
        return false;
    }
    private function createOptionsTable($tableName, $fieldsTable)
    {
        $this->info('creating '.$tableName);
        Schema::create($tableName, function (Blueprint $table) use($fieldsTable) {
            $table->id();
            $table->bigInteger('extraFieldId')->unsigned();
            $table->string('slug');
            $table->integer('value')->nullable();
            $table->string('label');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['extraFieldId', 'slug']);
            $table->foreign('extraFieldId')
                ->references('id')
                ->on($fieldsTable)
                ->cascadeOnDelete();
        });
    }

    private function checkHasValuesTable($table)
    {
        if (Schema::hasTable($table)) {
            if (!Schema::hasColumns($table, [
                'target_type', 'target_id', 'extraFieldId', 'value', 'row'
            ])) {
                $this->errors->add($table, "the $table was existed!");
            }
            return true;
        }
        return false;
    }
    private function createValuesTable($tableName, $fieldsTable)
    {
        $this->info('creating '.$tableName);
        Schema::create($tableName, function (Blueprint $table) use($fieldsTable) {
            $table->uuid('id');
            $table->morphs('target');
            $table->bigInteger('extraFieldId')->unsigned();
            $table->text('value')->nullable();
            $table->tinyInteger('row')->nullable();
            $table->foreign('extraFieldId')
                ->references('id')
                ->on($fieldsTable)
                ->cascadeOnDelete();
        });
    }
}
