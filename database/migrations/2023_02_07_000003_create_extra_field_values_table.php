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
        Schema::create(ExtraField::$tableValues, function (Blueprint $table) {
            $table->uuid('id');
            $table->morphs('target');
            $table->bigInteger('extraFieldId')->unsigned();
            $table->string('slug')->nullable();
            $table->text('value')->nullable();
            $table->tinyInteger('row')->nullable();
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
        Schema::dropIfExists(ExtraField::$tableValues);
    }
};
