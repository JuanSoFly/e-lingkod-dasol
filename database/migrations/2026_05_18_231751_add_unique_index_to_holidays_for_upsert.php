<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('holidays')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS holidays_date_scope_scope_code_unique ON holidays (date, scope, scope_code) NULLS NOT DISTINCT');

            return;
        }

        Schema::table('holidays', function ($table) {
            $table->unique(['date', 'scope', 'scope_code'], 'holidays_date_scope_scope_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('holidays')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS holidays_date_scope_scope_code_unique');

            return;
        }

        Schema::table('holidays', function ($table) {
            $table->dropUnique('holidays_date_scope_scope_code_unique');
        });
    }
};
