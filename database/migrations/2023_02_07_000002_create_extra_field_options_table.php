<?php
/**
 * Created by Vincent
 * @author vincent@pixodeo.net
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Contracts\Entities\Enums\ContractFieldTypeEnums;
use HXM\ExtraField\ExtraField;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(ExtraField::$tableOptions, function (Blueprint $table) {
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
                ->on(ExtraField::$tableFields)
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('extra_field.tables.options'));
    }
};
