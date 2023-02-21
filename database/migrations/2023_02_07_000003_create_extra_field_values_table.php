<?php
/**
 * Created by Vincent
 * @author vincent@pixodeo.net
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Contracts\Entities\Enums\ContractFieldTypeEnums;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('extra_field.tables.values'), function (Blueprint $table) {
            $table->uuid('id');
            $table->morphs('target');
            $table->bigInteger('extraFieldId')->unsigned();
            $table->text('value')->nullable();
            $table->tinyInteger('row')->nullable();
            $table->foreign('extraFieldId')
                ->references('id')
                ->on(config('extra_field.tables.fields'))
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('extra_field.tables.values'));
    }
};
