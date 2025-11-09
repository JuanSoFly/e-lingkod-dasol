<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_documents', 'storage_disk')) {
                $table->string('storage_disk', 50)->nullable()->after('file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            if (Schema::hasColumn('employee_documents', 'storage_disk')) {
                $table->dropColumn('storage_disk');
            }
        });
    }
};
