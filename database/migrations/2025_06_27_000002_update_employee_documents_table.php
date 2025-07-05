<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            // Add new fields for enhanced security
            $table->integer('file_size')->nullable()->after('file_path');
            $table->string('mime_type', 100)->nullable()->after('file_size');
            
            // Rename upload_date to uploaded_at for consistency
            $table->timestamp('uploaded_at')->nullable()->after('mime_type');
        });
        
        // Copy data from upload_date to uploaded_at
        DB::statement('UPDATE employee_documents SET uploaded_at = upload_date WHERE upload_date IS NOT NULL');
        
        // Drop old column
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->dropColumn('upload_date');
        });
    }

    public function down()
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->date('upload_date')->nullable();
            $table->dropColumn(['file_size', 'mime_type', 'uploaded_at']);
        });
    }
};