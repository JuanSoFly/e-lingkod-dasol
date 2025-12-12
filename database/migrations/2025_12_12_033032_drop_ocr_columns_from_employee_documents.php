<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->dropColumn([
                'extracted_content',
                'content_indexed_at',
                'content_hash',
                'search_metadata'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->longText('extracted_content')->nullable();
            $table->timestamp('content_indexed_at')->nullable();
            $table->string('content_hash')->nullable();
            $table->json('search_metadata')->nullable();
        });
    }
};
