<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds additional performance optimization indexes based on identified N+1 queries
     * and slow dashboard queries. These indexes target specific performance bottlenecks.
     */
    public function up(): void
    {
        // Add index for employee user relationship lookups
        Schema::table('users', function (Blueprint $table) {
            if (!$this->indexExists('users', 'idx_users_employee_id')) {
                $table->index('employee_id', 'idx_users_employee_id');
            }
        });

        // Add composite index for leave applications employee lookups with status
        Schema::table('leave_applications', function (Blueprint $table) {
            if (!$this->indexExists('leave_applications', 'idx_leave_apps_employee_status')) {
                $table->index(['employee_id', 'status'], 'idx_leave_apps_employee_status');
            }
        });

        // Add index for employee documents lookups
        Schema::table('employee_documents', function (Blueprint $table) {
            if (!$this->indexExists('employee_documents', 'idx_emp_docs_employee_id')) {
                $table->index('employee_id', 'idx_emp_docs_employee_id');
            }
            if (!$this->indexExists('employee_documents', 'idx_emp_docs_document_type')) {
                $table->index('document_type', 'idx_emp_docs_document_type');
            }
        });

        // Add index for performance targets employee lookups
        Schema::table('performance_targets', function (Blueprint $table) {
            if (!$this->indexExists('performance_targets', 'idx_perf_targets_employee_id')) {
                $table->index('employee_id', 'idx_perf_targets_employee_id');
            }
        });

        // Add index for performance reviews employee lookups
        Schema::table('performance_reviews', function (Blueprint $table) {
            if (!$this->indexExists('performance_reviews', 'idx_perf_reviews_employee_id')) {
                $table->index('employee_id', 'idx_perf_reviews_employee_id');
            }
        });

        // Add index for leave credits employee lookups
        Schema::table('leave_credits', function (Blueprint $table) {
            if (!$this->indexExists('leave_credits', 'idx_leave_credits_employee_id')) {
                $table->index('employee_id', 'idx_leave_credits_employee_id');
            }
        });

        // Add index for government benefits employee lookups (if table exists)
        if (Schema::hasTable('government_benefits')) {
            Schema::table('government_benefits', function (Blueprint $table) {
                if (!$this->indexExists('government_benefits', 'idx_gov_benefits_employee_id')) {
                    $table->index('employee_id', 'idx_gov_benefits_employee_id');
                }
                if (!$this->indexExists('government_benefits', 'idx_gov_benefits_type_status')) {
                    $table->index(['benefit_type', 'enrollment_status'], 'idx_gov_benefits_type_status');
                }
            });
        }

        // Add index for benefit contributions lookups (if table exists)
        if (Schema::hasTable('benefit_contributions')) {
            Schema::table('benefit_contributions', function (Blueprint $table) {
                if (!$this->indexExists('benefit_contributions', 'idx_benefit_contrib_employee_id')) {
                    $table->index('employee_id', 'idx_benefit_contrib_employee_id');
                }
                if (!$this->indexExists('benefit_contributions', 'idx_benefit_contrib_period')) {
                    $table->index(['contribution_period', 'payment_status'], 'idx_benefit_contrib_period');
                }
            });
        }

        // Add index for document versions lookups
        if (Schema::hasTable('document_versions')) {
            Schema::table('document_versions', function (Blueprint $table) {
                if (!$this->indexExists('document_versions', 'idx_doc_versions_original_id')) {
                    $table->index('original_document_id', 'idx_doc_versions_original_id');
                }
                if (!$this->indexExists('document_versions', 'idx_doc_versions_approval_status')) {
                    $table->index('approval_status', 'idx_doc_versions_approval_status');
                }
            });
        }

        // Add index for document links lookups
        if (Schema::hasTable('document_links')) {
            Schema::table('document_links', function (Blueprint $table) {
                if (!$this->indexExists('document_links', 'idx_doc_links_source')) {
                    $table->index(['source_type', 'source_id'], 'idx_doc_links_source');
                }
                if (!$this->indexExists('document_links', 'idx_doc_links_target')) {
                    $table->index(['target_type', 'target_id'], 'idx_doc_links_target');
                }
            });
        }

        // Add full-text search index for employee documents if not exists
        Schema::table('employee_documents', function (Blueprint $table) {
            // Check if ocr_content column exists before adding fulltext index
            $columns = Schema::getColumnListing('employee_documents');
            if (in_array('ocr_content', $columns) && !$this->indexExists('employee_documents', 'idx_emp_docs_search_content')) {
                $table->fullText(['file_name', 'description', 'ocr_content'], 'idx_emp_docs_search_content');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_employee_id');
        });

        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropIndex('idx_leave_apps_employee_status');
        });

        Schema::table('employee_documents', function (Blueprint $table) {
            $table->dropIndex('idx_emp_docs_employee_id');
            $table->dropIndex('idx_emp_docs_document_type');
            if ($this->indexExists('employee_documents', 'idx_emp_docs_search_content')) {
                $table->dropFullText('idx_emp_docs_search_content');
            }
        });

        Schema::table('performance_targets', function (Blueprint $table) {
            $table->dropIndex('idx_perf_targets_employee_id');
        });

        Schema::table('performance_reviews', function (Blueprint $table) {
            $table->dropIndex('idx_perf_reviews_employee_id');
        });

        Schema::table('leave_credits', function (Blueprint $table) {
            $table->dropIndex('idx_leave_credits_employee_id');
        });

        if (Schema::hasTable('government_benefits')) {
            Schema::table('government_benefits', function (Blueprint $table) {
                $table->dropIndex('idx_gov_benefits_employee_id');
                $table->dropIndex('idx_gov_benefits_type_status');
            });
        }

        if (Schema::hasTable('benefit_contributions')) {
            Schema::table('benefit_contributions', function (Blueprint $table) {
                $table->dropIndex('idx_benefit_contrib_employee_id');
                $table->dropIndex('idx_benefit_contrib_period');
            });
        }

        if (Schema::hasTable('document_versions')) {
            Schema::table('document_versions', function (Blueprint $table) {
                $table->dropIndex('idx_doc_versions_original_id');
                $table->dropIndex('idx_doc_versions_approval_status');
            });
        }

        if (Schema::hasTable('document_links')) {
            Schema::table('document_links', function (Blueprint $table) {
                $table->dropIndex('idx_doc_links_source');
                $table->dropIndex('idx_doc_links_target');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $connection = Schema::getConnection();

            if ($connection->getDriverName() === 'pgsql') {
                $result = $connection->select(
                    'select 1 from pg_indexes where schemaname = current_schema() and tablename = ? and indexname = ? limit 1',
                    [$table, $indexName]
                );

                return count($result) > 0;
            }

            $result = $connection->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

            return count($result) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
};
