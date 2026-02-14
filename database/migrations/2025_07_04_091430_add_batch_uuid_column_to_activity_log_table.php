<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBatchUuidColumnToActivityLogTable extends Migration
{
    public function up()
    {
        $connection = config('activitylog.database_connection');
        $tableName = config('activitylog.table_name');

        if (! Schema::connection($connection)->hasTable($tableName)) {
            return;
        }

        if (Schema::connection($connection)->hasColumn($tableName, 'batch_uuid')) {
            return;
        }

        $hasPropertiesColumn = Schema::connection($connection)->hasColumn($tableName, 'properties');

        Schema::connection($connection)->table($tableName, function (Blueprint $table) use ($hasPropertiesColumn) {
            $column = $table->uuid('batch_uuid')->nullable();

            if ($hasPropertiesColumn) {
                $column->after('properties');
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

        if (! Schema::connection($connection)->hasColumn($tableName, 'batch_uuid')) {
            return;
        }

        Schema::connection($connection)->table($tableName, function (Blueprint $table) {
            $table->dropColumn('batch_uuid');
        });
    }
}
