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
        Schema::create(ExtraField::$tableFields, function (Blueprint $table) {
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
            $table->unique(['target_type', 'target_id', 'parentId', 'slug']);
            $table->foreign('parentId')
                ->on(ExtraField::$tableFields)
                ->references('id')
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
        Schema::dropIfExists(ExtraField::$tableFields);
    }
};
