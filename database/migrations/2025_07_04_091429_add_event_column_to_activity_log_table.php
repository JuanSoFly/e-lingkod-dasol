<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEventColumnToActivityLogTable extends Migration
{
    public function up()
    {
        $connection = config('activitylog.database_connection');
        $tableName = config('activitylog.table_name');

        if (! Schema::connection($connection)->hasTable($tableName)) {
            return;
        }

        if (Schema::connection($connection)->hasColumn($tableName, 'event')) {
            return;
        }

        $hasSubjectTypeColumn = Schema::connection($connection)->hasColumn($tableName, 'subject_type');

        Schema::connection($connection)->table($tableName, function (Blueprint $table) use ($hasSubjectTypeColumn) {
            $column = $table->string('event')->nullable();

            if ($hasSubjectTypeColumn) {
                $column->after('subject_type');
            }
        });
    }

    public function down()
    {
        $connection = config('activitylog.database_connection');
        $tableName = config('activitylog.table_name');

        if (! Schema::connection($connection)->hasTable($tableName)) {
            return;
        }

        if (! Schema::connection($connection)->hasColumn($tableName, 'event')) {
            return;
        }

        Schema::connection($connection)->table($tableName, function (Blueprint $table) {
            $table->dropColumn('event');
        });
    }
}
