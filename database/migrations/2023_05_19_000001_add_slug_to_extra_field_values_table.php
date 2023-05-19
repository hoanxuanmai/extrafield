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
        if (! Schema::hasColumn(ExtraField::$tableValues, 'slug')) {
            Schema::table(ExtraField::$tableValues, function (Blueprint $table) {
                $table->string('slug')->nullable()->after('extraFieldId');
            });
            Schema::table(ExtraField::$tableFields, function(Blueprint $table) {

                $table->unsignedBigInteger('parentId')->nullable()->change();
                \Illuminate\Support\Facades\DB::table(ExtraField::$tableFields)
                    ->where('parentId', 0)
                    ->update([
                        'parentId' => null
                    ]);
            });
            $values = app(ExtraField::$modelValue)->get();
            $fieldIds = $values->pluck('extraFieldId');
            $fields = app(ExtraField::$modelField)->whereIn('id', $fieldIds)->get()->mapWithKeys(function ($dt) {
                return [$dt->id => $dt];
            });
            foreach ($values as $value) {
                $field = $fields[$value->extraFieldId];
                $treeSlug = [$field->parentInput, $field->slug];
                if (!is_null($value->row)) {
                    $treeSlug[] = $value->row;
                }
                $slug = implode('.', $treeSlug);
                $value->update(['slug' => $slug]);
            }

        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropColumns(ExtraField::$tableValues, 'slug');
    }
};
