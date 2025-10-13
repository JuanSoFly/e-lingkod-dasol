<?php

namespace App\Console\Commands;

use App\Models\ExportAuditLog;
use App\Models\QueuedExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExportCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exports:cleanup
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--days=7 : Delete exports older than this many days}
                            {--keep-completed=30 : Keep completed exports for this many days}
                            {--keep-failed=7 : Keep failed exports for this many days}
                            {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old export files and database records';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $days = (int) $this->option('days');
        $keepCompleted = (int) $this->option('keep-completed');
        $keepFailed = (int) $this->option('keep-failed');
        $force = $this->option('force');

        $this->info('🧹 Starting PDS export cleanup...');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No files will be deleted');
        }

        // Initialize cleanup statistics
        $stats = [
            'files_found' => 0,
            'files_deleted' => 0,
            'audit_logs_cleaned' => 0,
            'queued_exports_cleaned' => 0,
            'disk_space_freed' => 0,
            'errors' => 0,
        ];

        try {
            // 1. Clean up old export files
            $this->line('📁 Cleaning up export files...');
            $fileStats = $this->cleanupExportFiles($days, $keepCompleted, $keepFailed, $dryRun);
            $stats = array_merge($stats, $fileStats);

            // 2. Clean up old audit log entries
            $this->line('📋 Cleaning up audit log entries...');
            $auditStats = $this->cleanupAuditLogs($days, $dryRun);
            $stats['audit_logs_cleaned'] = $auditStats;

            // 3. Clean up old queued export records
            $this->line('🔄 Cleaning up queued export records...');
            $queueStats = $this->cleanupQueuedExports($keepCompleted, $keepFailed, $dryRun);
            $stats['queued_exports_cleaned'] = $queueStats;

            // 4. Clean up orphaned records
            $this->line('🔍 Cleaning up orphaned records...');
            $orphanStats = $this->cleanupOrphanedRecords($dryRun);
            $stats['errors'] += $orphanStats['errors'];

            // Display results
            $this->displayResults($stats, $dryRun);

            if (!$dryRun && $stats['files_deleted'] > 0) {
                $this->info('✨ Export cleanup completed successfully!');
            } elseif ($dryRun) {
                $this->info('🔍 Dry run completed. Use --force to actually delete files.');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Export cleanup failed: ' . $e->getMessage());
            Log::error('Export cleanup command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    /**
     * Clean up old export files from storage
     */
    protected function cleanupExportFiles(int $days, int $keepCompleted, int $keepFailed, bool $dryRun): array
    {
        $stats = [
            'files_found' => 0,
            'files_deleted' => 0,
            'disk_space_freed' => 0,
        ];

        $exportPath = 'exports/';

        if (!Storage::exists($exportPath)) {
            $this->line('  ℹ️ No exports directory found.');
            return $stats;
        }

        $cutoffDate = Carbon::now()->subDays($days);
        $completedCutoff = Carbon::now()->subDays($keepCompleted);
        $failedCutoff = Carbon::now()->subDays($keepFailed);

        // Get all files in exports directory
        $files = Storage::allFiles($exportPath);

        foreach ($files as $file) {
            $filePath = $exportPath . $file;
            $fullPath = storage_path('app/' . $filePath);

            if (!file_exists($fullPath)) {
                continue;
            }

            $fileStats = stat($fullPath);
            $fileModified = Carbon::createFromTimestamp($fileStats['mtime']);

            $stats['files_found']++;

            // Check if file should be deleted
            $shouldDelete = false;
            $deleteReason = '';

            if ($fileModified->lt($cutoffDate)) {
                $shouldDelete = true;
                $deleteReason = 'older than ' . $days . ' days';
            } else {
                // Check audit log for this file to determine status-based cleanup
                $auditLog = ExportAuditLog::where('file_name', basename($file))
                    ->where('created_at', '>=', $fileModified->subMinutes(5))
                    ->first();

                if ($auditLog) {
                    $createdAt = $auditLog->created_at;

                    // Check if there's a corresponding queued export
                    $queuedExport = QueuedExport::where('job_id', 'like', '%' . basename($file, '.xlsx') . '%')
                        ->first();

                    if ($queuedExport) {
                        if ($queuedExport->status === 'completed' && $createdAt->lt($completedCutoff)) {
                            $shouldDelete = true;
                            $deleteReason = 'completed export older than ' . $keepCompleted . ' days';
                        } elseif ($queuedExport->status === 'failed' && $createdAt->lt($failedCutoff)) {
                            $shouldDelete = true;
                            $deleteReason = 'failed export older than ' . $keepFailed . ' days';
                        }
                    }
                }
            }

            if ($shouldDelete) {
                $fileSize = $fileStats['size'];

                if ($dryRun) {
                    $this->line("  📄 Would delete: {$file} ({$deleteReason}, " . $this->formatBytes($fileSize) . ")");
                } else {
                    if (Storage::delete($filePath)) {
                        $stats['files_deleted']++;
                        $stats['disk_space_freed'] += $fileSize;
                        $this->line("  🗑️ Deleted: {$file} ({$deleteReason})");

                        Log::info('Export file cleaned up', [
                            'file' => $file,
                            'reason' => $deleteReason,
                            'size' => $fileSize,
                        ]);
                    } else {
                        $this->line("  ❌ Failed to delete: {$file}");
                        $stats['errors']++;
                    }
                }
            }
        }

        // Clean up empty directories
        if (!$dryRun) {
            $this->cleanupEmptyDirectories($exportPath);
        }

        return $stats;
    }

    /**
     * Clean up old audit log entries
     */
    protected function cleanupAuditLogs(int $days, bool $dryRun): int
    {
        $cutoffDate = Carbon::now()->subDays($days);
        $query = ExportAuditLog::where('created_at', '<', $cutoffDate);

        $count = $query->count();

        if ($count > 0) {
            if ($dryRun) {
                $this->line("  📋 Would delete {$count} old audit log entries");
            } else {
                $deleted = $query->delete();
                $this->line("  🗑️ Deleted {$deleted} old audit log entries");

                Log::info('Audit logs cleaned up', [
                    'count' => $deleted,
                    'cutoff_date' => $cutoffDate->toDateTimeString(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Clean up old queued export records
     */
    protected function cleanupQueuedExports(int $keepCompleted, int $keepFailed, bool $dryRun): int
    {
        $completedCutoff = Carbon::now()->subDays($keepCompleted);
        $failedCutoff = Carbon::now()->subDays($keepFailed);

        $completedCount = QueuedExport::where('status', 'completed')
            ->where('completed_at', '<', $completedCutoff)
            ->count();

        $failedCount = QueuedExport::where('status', 'failed')
            ->where('updated_at', '<', $failedCutoff)
            ->count();

        $totalToDelete = $completedCount + $failedCount;

        if ($totalToDelete > 0) {
            if ($dryRun) {
                $this->line("  🔄 Would delete {$completedCount} completed + {$failedCount} failed queued export records");
            } else {
                $deletedCompleted = QueuedExport::where('status', 'completed')
                    ->where('completed_at', '<', $completedCutoff)
                    ->delete();

                $deletedFailed = QueuedExport::where('status', 'failed')
                    ->where('updated_at', '<', $failedCutoff)
                    ->delete();

                $totalDeleted = $deletedCompleted + $deletedFailed;
                $this->line("  🗑️ Deleted {$totalDeleted} queued export records ({$deletedCompleted} completed, {$deletedFailed} failed)");

                Log::info('Queued exports cleaned up', [
                    'completed_deleted' => $deletedCompleted,
                    'failed_deleted' => $deletedFailed,
                    'total_deleted' => $totalDeleted,
                ]);
            }
        }

        return $totalToDelete;
    }

    /**
     * Clean up orphaned records
     */
    protected function cleanupOrphanedRecords(bool $dryRun): array
    {
        $stats = ['errors' => 0];

        // Find audit logs with files that no longer exist
        $orphanedLogs = ExportAuditLog::whereNotNull('file_name')
            ->where('file_name', '!=', '')
            ->get()
            ->filter(function ($log) {
                return !Storage::exists('exports/' . $log->file_name);
            });

        if ($orphanedLogs->count() > 0) {
            if ($dryRun) {
                $this->line("  🔍 Found {$orphanedLogs->count()} orphaned audit log entries");
            } else {
                $deleted = $orphanedLogs->each(function ($log) {
                    $log->update(['file_name' => null, 'file_size' => 0]);
                });
                $this->line("  🔧 Updated {$orphanedLogs->count()} orphaned audit log entries");
            }
        }

        // Find queued exports with files that no longer exist
        $orphanedQueued = QueuedExport::whereNotNull('file_path')
            ->where('file_path', '!=', '')
            ->get()
            ->filter(function ($queued) {
                return !Storage::exists($queued->file_path);
            });

        if ($orphanedQueued->count() > 0) {
            if ($dryRun) {
                $this->line("  🔍 Found {$orphanedQueued->count()} orphaned queued export records");
            } else {
                $deleted = $orphanedQueued->each(function ($queued) {
                    $queued->update(['file_path' => null, 'status' => 'failed', 'error_message' => 'File not found']);
                });
                $this->line("  🔧 Updated {$orphanedQueued->count()} orphaned queued export records");
            }
        }

        return $stats;
    }

    /**
     * Clean up empty directories in the export path
     */
    protected function cleanupEmptyDirectories(string $path): void
    {
        // This is a simple implementation - could be made more robust
        try {
            $directories = Storage::allDirectories($path);

            // Check directories in reverse order (deepest first)
            foreach (array_reverse($directories) as $dir) {
                $fullPath = storage_path('app/' . $dir);

                if (is_dir($fullPath) && $this->isDirEmpty($fullPath)) {
                    rmdir($fullPath);
                    Log::info('Empty directory removed', ['directory' => $dir]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clean up empty directories', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if a directory is empty
     */
    protected function isDirEmpty(string $dir): bool
    {
        return count(scandir($dir)) === 2; // Only . and .. entries
    }

    /**
     * Display cleanup results
     */
    protected function displayResults(array $stats, bool $dryRun): void
    {
        $this->newLine();
        $this->info('📊 Cleanup Summary:');
        $this->newLine();

        if ($dryRun) {
            $this->line("📁 Files found: {$stats['files_found']}");
            $this->line("📁 Files to delete: {$stats['files_found']}");
        } else {
            $this->line("📁 Files processed: {$stats['files_found']}");
            $this->line("🗑️  Files deleted: {$stats['files_deleted']}");
            $this->line("💾 Disk space freed: " . $this->formatBytes($stats['disk_space_freed']));
        }

        $this->line("📋 Audit logs cleaned: {$stats['audit_logs_cleaned']}");
        $this->line("🔄 Queued exports cleaned: {$stats['queued_exports_cleaned']}");

        if ($stats['errors'] > 0) {
            $this->line("❌ Errors encountered: {$stats['errors']}");
        }
    }

    /**
     * Format bytes for human readable display
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}