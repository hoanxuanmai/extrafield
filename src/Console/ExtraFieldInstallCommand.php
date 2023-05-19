<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

namespace HXM\ExtraField\Console;

use HXM\ExtraField\Contracts\CanAccessExtraFieldValueInterface;
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

    protected array $currentConfigTables = [];

    private ?Model $currentInstance;

    public function __construct()
    {
        parent::__construct();
        $this->errors = new MessageBag();
    }

    function handle()
    {
        $privates = config('extra_field.privates', []);
        $installs = [];
        foreach ($privates as $target => $tables) {
            if (is_string($tables)) {
                $target = $tables;
                $tables = [];
            }
            /** @var Model $targetInstance */
            $targetInstance = app()->make($target);

            if ($targetInstance instanceof CanAccessExtraFieldValueInterface) {
                $this->currentInstance = $targetInstance;
                $this->currentConfigTables = ExtraField::getPriviteTables($target);
//                dd(ExtraField::getPriviteTables($target), ExtraField::getPriviteTables(get_class($this->currentInstance)));
                $installs[] = $this->checkInstalled($tables);
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
    }

    private function checkInstalled(array $tables)
    {
        return [
            'fields' => $this->checkHasFieldsTable(null),
            'options' => $this->checkHasOptionsTable(null),
            'values' => $this->checkHasValuesTable(null),
        ];
    }

    private function createTables($tables)
    {
        empty($tables['fields']['exist']) && $this->createFieldsTable($tables['fields']['table']);
        empty($tables['options']['exist']) && $this->createOptionsTable($tables['options']['table'], $tables['fields']['table']);
        empty($tables['values']['exist']) && $this->createValuesTable($tables['values']['table'], $tables['fields']['table']);
    }

    private function checkHasFieldsTable($prefix)
    {
        $table = $prefix.$this->currentConfigTables['fields'];
        $data = [
            'table' => $table,
            'exist' => false,
        ];
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
            $data['exist'] = true;
        }
        return $data;
    }
    private function createFieldsTable($tableName)
    {
        $this->info('creating '.$tableName);
        Schema::create($tableName, function (Blueprint $table) use ($tableName) {
            $table->id();
            $table->morphs('target');
            $table->unsignedBigInteger('parentId')->nullable();
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
            $table->unique(['target_id', 'parentId', 'slug'], 'target_id_parentId_slug_unique');
            $table->foreign('parentId')
                ->on($tableName)
                ->references('id')
                ->cascadeOnDelete();
        });
    }

    private function checkHasOptionsTable($prefix)
    {
        $table = $prefix.$this->currentConfigTables['options'];
        $data = [
            'table' => $table,
            'exist' => false,
        ];
        if (Schema::hasTable($table)) {
            if (!Schema::hasColumns($table, [
                'extraFieldId',
                'value',
                'label',
            ])) {
                $this->errors->add($table, "the $table was existed!");
            }
            $data['exist'] = true;
        }
        return $data;
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

    private function checkHasValuesTable($prefix)
    {
        $table = $prefix.$this->currentConfigTables['values'];
        $data = [
            'table' => $table,
            'exist' => false,
        ];
        if (Schema::hasTable($table)) {
            if (!Schema::hasColumns($table, [
                'target_type', 'target_id', 'extraFieldId','slug', 'value', 'row'
            ])) {
                $this->errors->add($table, "the $table was existed!");
            }
            $data['exist'] = true;
        }
        return $data;
    }
    private function createValuesTable($tableName, $fieldsTable)
    {
        $this->info('creating '.$tableName);
        Schema::create($tableName, function (Blueprint $table) use($fieldsTable) {
            $table->uuid('id');
            $table->morphs('target');
            $table->bigInteger('extraFieldId')->unsigned();
            $table->string('slug')->nullable();
            $table->text('value')->nullable();
            $table->tinyInteger('row')->nullable();
            $table->foreign('extraFieldId')
                ->references('id')
                ->on($fieldsTable)
                ->cascadeOnDelete();
        });
    }
}
