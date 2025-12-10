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
        // Add missing indexes to leave_applications table
        Schema::table('leave_applications', function (Blueprint $table) {
            // Add index for pending approvals by date range (if not exists)
            if (!$this->indexExists('leave_applications', 'idx_pending_date_range')) {
                $table->index(['status', 'start_date', 'end_date'], 'idx_pending_date_range');
            }
            
            // Add separate year index for common year-based queries (if not exists)
            if (!$this->indexExists('leave_applications', 'idx_leave_date_lookup')) {
                $table->index(['employee_id', 'leave_type_id', 'start_date'], 'idx_leave_date_lookup');
            }
        });

        // Optimize leave_credits table (if indexes don't exist)
        Schema::table('leave_credits', function (Blueprint $table) {
            if (!$this->indexExists('leave_credits', 'idx_leave_credit_lookup')) {
                $table->index(['employee_id', 'leave_type_id', 'year'], 'idx_leave_credit_lookup');
            }
            
            if (!$this->indexExists('leave_credits', 'idx_remaining_credits')) {
                $table->index(['employee_id', 'year', 'remaining_credits'], 'idx_remaining_credits');
            }
        });

        // Optimize leave_policies table (if indexes don't exist)
        Schema::table('leave_policies', function (Blueprint $table) {
            if (!$this->indexExists('leave_policies', 'idx_policy_effective')) {
                $table->index(['is_active', 'effective_start_date', 'effective_end_date'], 'idx_policy_effective');
            }
            
            if (!$this->indexExists('leave_policies', 'idx_policy_type_employee')) {
                $table->index(['employee_type', 'leave_type_id', 'is_active'], 'idx_policy_type_employee');
            }
        });

        // Add leave_application_workflow_steps optimization (if indexes don't exist)
        if (Schema::hasTable('leave_application_workflow_steps')) {
            Schema::table('leave_application_workflow_steps', function (Blueprint $table) {
                if (!$this->indexExists('leave_application_workflow_steps', 'idx_workflow_pending')) {
                    $table->index(['leave_application_id', 'status', 'step_order'], 'idx_workflow_pending');
                }
                
                if (!$this->indexExists('leave_application_workflow_steps', 'idx_escalation_processing')) {
                    $table->index(['status', 'created_at'], 'idx_escalation_processing');
                }
            });
        }

        // Add leave_workflow_steps optimization (if indexes don't exist)
        if (Schema::hasTable('leave_workflow_steps')) {
            Schema::table('leave_workflow_steps', function (Blueprint $table) {
                if (!$this->indexExists('leave_workflow_steps', 'idx_workflow_routing')) {
                    $table->index(['leave_workflow_id', 'step_order'], 'idx_workflow_routing');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from leave_applications (if they exist)
        $this->dropIndexIfExists('leave_applications', 'idx_pending_date_range');
        $this->dropIndexIfExists('leave_applications', 'idx_leave_date_lookup');

        // Remove indexes from leave_credits (if they exist)
        $this->dropIndexIfExists('leave_credits', 'idx_leave_credit_lookup');
        $this->dropIndexIfExists('leave_credits', 'idx_remaining_credits');

        // Remove indexes from leave_policies (if they exist)
        $this->dropIndexIfExists('leave_policies', 'idx_policy_effective');
        $this->dropIndexIfExists('leave_policies', 'idx_policy_type_employee');

        // Remove indexes from leave_application_workflow_steps (if they exist)
        if (Schema::hasTable('leave_application_workflow_steps')) {
            $this->dropIndexIfExists('leave_application_workflow_steps', 'idx_workflow_pending');
            $this->dropIndexIfExists('leave_application_workflow_steps', 'idx_escalation_processing');
        }

        // Remove indexes from leave_workflow_steps (if they exist)
        if (Schema::hasTable('leave_workflow_steps')) {
            $this->dropIndexIfExists('leave_workflow_steps', 'idx_workflow_routing');
        }
    }

    /**
     * Check if an index exists on a table
     */
    protected function indexExists(string $table, string $index): bool
    {
        return Schema::hasTable($table) && \DB::select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
            WHERE table_schema = DATABASE() 
            AND table_name = ? 
            AND index_name = ?",
            [$table, $index]
        )[0]->count > 0;
    }

    /**
     * Drop an index if it exists
     */
    protected function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            Schema::table($table, function (Blueprint $table) use ($index) {
                $table->dropIndex($index);
            });
        }
    }
};