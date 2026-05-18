<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            // Add fields for document content indexing and search optimization
            $table->text('extracted_content')->nullable()->after('mime_type');
            $table->timestamp('content_indexed_at')->nullable()->after('extracted_content');
            $table->string('content_hash', 64)->nullable()->after('content_indexed_at');
            $table->json('search_metadata')->nullable()->after('content_hash');
        });

        // Add database indexes for improved search performance
        $this->addSearchIndexes();

        // Add full-text indexes for database search optimization
        $this->addFullTextIndexes();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop full-text indexes first
        $this->dropFullTextIndexes();
        
        // Drop regular indexes
        $this->dropSearchIndexes();

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
     * Add search optimization indexes
     */
    private function addSearchIndexes(): void
    {
        // Composite index for common search patterns
        DB::statement('CREATE INDEX idx_employee_documents_search ON employee_documents (document_type, uploaded_at DESC)');
        
        // Index on employee_id for permission-based filtering
        DB::statement('CREATE INDEX idx_employee_documents_employee_uploaded ON employee_documents (employee_id, uploaded_at DESC)');
        
        // Index on file metadata for filtering
        DB::statement('CREATE INDEX idx_employee_documents_metadata ON employee_documents (mime_type, file_size, uploaded_at DESC)');
        
        // Index on uploader for tracking
        DB::statement('CREATE INDEX idx_employee_documents_uploader ON employee_documents (uploaded_by, uploaded_at DESC)');
        
        // Index for content indexing status
        DB::statement('CREATE INDEX idx_employee_documents_content_indexed ON employee_documents (content_indexed_at, content_hash)');
    }

    /**
     * Add full-text search indexes
     */
    private function addFullTextIndexes(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("CREATE INDEX idx_employee_documents_filename_fulltext ON employee_documents USING GIN (to_tsvector('simple', coalesce(file_name, '')))");
            DB::statement("CREATE INDEX idx_employee_documents_content_fulltext ON employee_documents USING GIN (to_tsvector('simple', coalesce(extracted_content, '')))");

            return;
        }

        // Full-text index on file names for advanced search
        DB::statement('CREATE FULLTEXT INDEX idx_employee_documents_filename_fulltext ON employee_documents (file_name)');
        
        // Full-text index on extracted content (will be populated by indexing service)
        DB::statement('CREATE FULLTEXT INDEX idx_employee_documents_content_fulltext ON employee_documents (extracted_content)');
    }

    /**
     * Drop search optimization indexes
     */
    private function dropSearchIndexes(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS idx_employee_documents_search');
            DB::statement('DROP INDEX IF EXISTS idx_employee_documents_employee_uploaded');
            DB::statement('DROP INDEX IF EXISTS idx_employee_documents_metadata');
            DB::statement('DROP INDEX IF EXISTS idx_employee_documents_uploader');
            DB::statement('DROP INDEX IF EXISTS idx_employee_documents_content_indexed');

            return;
        }

        DB::statement('DROP INDEX IF EXISTS idx_employee_documents_search ON employee_documents');
        DB::statement('DROP INDEX IF EXISTS idx_employee_documents_employee_uploaded ON employee_documents');
        DB::statement('DROP INDEX IF EXISTS idx_employee_documents_metadata ON employee_documents');
        DB::statement('DROP INDEX IF EXISTS idx_employee_documents_uploader ON employee_documents');
        DB::statement('DROP INDEX IF EXISTS idx_employee_documents_content_indexed ON employee_documents');
    }

    /**
     * Drop full-text search indexes
     */
    private function dropFullTextIndexes(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS idx_employee_documents_filename_fulltext');
            DB::statement('DROP INDEX IF EXISTS idx_employee_documents_content_fulltext');

            return;
        }

        DB::statement('DROP INDEX IF EXISTS idx_employee_documents_filename_fulltext ON employee_documents');
        DB::statement('DROP INDEX IF EXISTS idx_employee_documents_content_fulltext ON employee_documents');
    }
};
