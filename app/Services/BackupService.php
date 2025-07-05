<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use ZipArchive;

/**
 * Comprehensive Backup Service for E-Lingkod Dasol HRIS
 * 
 * Provides automated database and file backups with external storage integration
 * Addresses Mr. Bryan's requirement for "secured database for all HR files and backup through external hard drives"
 */
class BackupService
{
    /**
     * Backup configuration
     */
    private const BACKUP_CONFIG = [
        'database' => [
            'enabled' => true,
            'retention_days' => 30,
            'compression' => true,
        ],
        'files' => [
            'enabled' => true,
            'retention_days' => 60,
            'include_directories' => [
                'private/employee_documents',
                'private/reports',
                'private/exports',
            ],
        ],
        'external_storage' => [
            'enabled' => true,
            'drives' => ['D:', 'E:', 'F:'], // Common external drive letters
            'backup_folder' => 'HRIS_Backups',
        ],
    ];

    /**
     * Create comprehensive system backup
     */
    public function createFullBackup(array $options = []): array
    {
        try {
            Log::info('Starting full system backup', $options);
            
            $backupId = 'backup_' . now()->format('Y_m_d_H_i_s');
            $results = [
                'backup_id' => $backupId,
                'started_at' => now(),
                'database_backup' => null,
                'files_backup' => null,
                'external_storage' => [],
                'errors' => [],
                'success' => false,
            ];

            // Create database backup
            if (self::BACKUP_CONFIG['database']['enabled']) {
                $results['database_backup'] = $this->createDatabaseBackup($backupId, $options);
            }

            // Create files backup
            if (self::BACKUP_CONFIG['files']['enabled']) {
                $results['files_backup'] = $this->createFilesBackup($backupId, $options);
            }

            // Copy to external storage
            if (self::BACKUP_CONFIG['external_storage']['enabled']) {
                $results['external_storage'] = $this->copyToExternalStorage($backupId, $results);
            }

            // Clean old backups
            $this->cleanOldBackups();

            $results['completed_at'] = now();
            $results['success'] = true;
            $results['duration'] = $results['completed_at']->diffInSeconds($results['started_at']);

            Log::info('Full system backup completed successfully', [
                'backup_id' => $backupId,
                'duration' => $results['duration'],
                'database_size' => $results['database_backup']['size_mb'] ?? 0,
                'files_size' => $results['files_backup']['size_mb'] ?? 0,
            ]);

            return $results;

        } catch (\Exception $e) {
            Log::error('Full system backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $results['errors'][] = $e->getMessage();
            $results['completed_at'] = now();
            return $results;
        }
    }

    /**
     * Create MySQL database backup
     */
    public function createDatabaseBackup(string $backupId, array $options = []): array
    {
        try {
            $config = config('database.connections.mysql');
            $timestamp = now()->format('Y_m_d_H_i_s');
            $filename = "database_backup_{$timestamp}.sql";
            
            if (self::BACKUP_CONFIG['database']['compression']) {
                $filename .= '.gz';
            }

            $backupPath = storage_path("app/backups/database/{$filename}");
            $this->ensureDirectoryExists(dirname($backupPath));

            // Create mysqldump command
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s --port=%s --single-transaction --routines --triggers %s',
                escapeshellarg($config['username']),
                escapeshellarg($config['password']),
                escapeshellarg($config['host']),
                escapeshellarg($config['port']),
                escapeshellarg($config['database'])
            );

            // Add compression if enabled
            if (self::BACKUP_CONFIG['database']['compression']) {
                $command .= ' | gzip';
            }

            $command .= ' > ' . escapeshellarg($backupPath);

            // Execute backup command
            $result = shell_exec($command . ' 2>&1');
            
            if (!file_exists($backupPath) || filesize($backupPath) === 0) {
                throw new \Exception("Database backup failed: {$result}");
            }

            $fileSize = filesize($backupPath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);

            // Verify backup integrity
            $verificationResult = $this->verifyDatabaseBackup($backupPath);

            Log::info('Database backup created successfully', [
                'backup_id' => $backupId,
                'filename' => $filename,
                'size_mb' => $fileSizeMB,
                'verification' => $verificationResult,
            ]);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $backupPath,
                'size_bytes' => $fileSize,
                'size_mb' => $fileSizeMB,
                'compressed' => self::BACKUP_CONFIG['database']['compression'],
                'verification' => $verificationResult,
                'created_at' => now(),
            ];

        } catch (\Exception $e) {
            Log::error('Database backup failed', [
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'created_at' => now(),
            ];
        }
    }

    /**
     * Create files backup (employee documents, reports, etc.)
     */
    public function createFilesBackup(string $backupId, array $options = []): array
    {
        try {
            $timestamp = now()->format('Y_m_d_H_i_s');
            $filename = "files_backup_{$timestamp}.zip";
            $backupPath = storage_path("app/backups/files/{$filename}");
            $this->ensureDirectoryExists(dirname($backupPath));

            $zip = new ZipArchive();
            if ($zip->open($backupPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new \Exception('Cannot create ZIP backup file');
            }

            $totalFiles = 0;
            $totalSize = 0;

            // Add files from configured directories
            foreach (self::BACKUP_CONFIG['files']['include_directories'] as $directory) {
                $fullPath = storage_path("app/{$directory}");
                
                if (is_dir($fullPath)) {
                    $files = File::allFiles($fullPath);
                    
                    foreach ($files as $file) {
                        $relativePath = str_replace(storage_path('app/'), '', $file->getPathname());
                        $zip->addFile($file->getPathname(), $relativePath);
                        $totalFiles++;
                        $totalSize += $file->getSize();
                    }

                    Log::info("Added directory to backup: {$directory}", [
                        'files_count' => count($files),
                    ]);
                }
            }

            // Add backup metadata
            $metadata = [
                'backup_id' => $backupId,
                'created_at' => now()->toISOString(),
                'total_files' => $totalFiles,
                'total_size_bytes' => $totalSize,
                'directories_included' => self::BACKUP_CONFIG['files']['include_directories'],
                'system_info' => [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'mysql_version' => DB::select('SELECT VERSION() as version')[0]->version,
                ],
            ];

            $zip->addFromString('backup_metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));
            $zip->close();

            $fileSize = filesize($backupPath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);

            Log::info('Files backup created successfully', [
                'backup_id' => $backupId,
                'filename' => $filename,
                'total_files' => $totalFiles,
                'size_mb' => $fileSizeMB,
            ]);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $backupPath,
                'size_bytes' => $fileSize,
                'size_mb' => $fileSizeMB,
                'total_files' => $totalFiles,
                'directories_included' => self::BACKUP_CONFIG['files']['include_directories'],
                'created_at' => now(),
            ];

        } catch (\Exception $e) {
            Log::error('Files backup failed', [
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'created_at' => now(),
            ];
        }
    }

    /**
     * Copy backups to external storage drives
     */
    public function copyToExternalStorage(string $backupId, array $backupResults): array
    {
        $externalResults = [];

        foreach (self::BACKUP_CONFIG['external_storage']['drives'] as $drive) {
            try {
                $drivePath = $drive . '\\' . self::BACKUP_CONFIG['external_storage']['backup_folder'];
                
                // Check if drive exists and is accessible
                if (!is_dir($drive)) {
                    $externalResults[$drive] = [
                        'success' => false,
                        'error' => 'Drive not accessible or not connected',
                    ];
                    continue;
                }

                // Create backup folder structure
                $backupFolder = $drivePath . '\\' . now()->format('Y-m-d') . '\\' . $backupId;
                if (!File::exists($backupFolder)) {
                    File::makeDirectory($backupFolder, 0755, true);
                }

                $copiedFiles = [];

                // Copy database backup
                if (isset($backupResults['database_backup']['path'])) {
                    $sourceFile = $backupResults['database_backup']['path'];
                    $destFile = $backupFolder . '\\' . basename($sourceFile);
                    
                    if (File::copy($sourceFile, $destFile)) {
                        $copiedFiles[] = 'database_backup';
                        Log::info("Database backup copied to external drive", [
                            'drive' => $drive,
                            'source' => $sourceFile,
                            'destination' => $destFile,
                        ]);
                    }
                }

                // Copy files backup
                if (isset($backupResults['files_backup']['path'])) {
                    $sourceFile = $backupResults['files_backup']['path'];
                    $destFile = $backupFolder . '\\' . basename($sourceFile);
                    
                    if (File::copy($sourceFile, $destFile)) {
                        $copiedFiles[] = 'files_backup';
                        Log::info("Files backup copied to external drive", [
                            'drive' => $drive,
                            'source' => $sourceFile,
                            'destination' => $destFile,
                        ]);
                    }
                }

                // Create backup summary
                $summaryFile = $backupFolder . '\\backup_summary.txt';
                $summary = $this->generateBackupSummary($backupId, $backupResults);
                File::put($summaryFile, $summary);

                $externalResults[$drive] = [
                    'success' => true,
                    'backup_folder' => $backupFolder,
                    'copied_files' => $copiedFiles,
                    'copied_at' => now(),
                ];

            } catch (\Exception $e) {
                Log::error("Failed to copy backup to external drive: {$drive}", [
                    'backup_id' => $backupId,
                    'error' => $e->getMessage(),
                ]);

                $externalResults[$drive] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $externalResults;
    }

    /**
     * Generate physical copy of employee records
     */
    public function generatePhysicalCopy(int $employeeId, array $options = []): array
    {
        try {
            $employee = \App\Models\Employee::with([
                'documents',
                'leaveApplications',
                'performanceReviews',
                'governmentBenefits',
                'civilServiceEligibilities',
                'careerProgressions',
                'employeeTrainings',
                'employeeScholarships'
            ])->findOrFail($employeeId);

            $timestamp = now()->format('Y_m_d_H_i_s');
            $filename = "employee_record_{$employee->employee_number}_{$timestamp}.pdf";
            $pdfPath = storage_path("app/physical_copies/{$filename}");
            $this->ensureDirectoryExists(dirname($pdfPath));

            // Generate comprehensive PDF report
            $data = [
                'employee' => $employee,
                'generated_at' => now(),
                'generated_by' => auth()->user()?->name ?? 'System',
                'include_sensitive' => $options['include_sensitive'] ?? false,
                'watermark' => $options['watermark'] ?? 'OFFICIAL COPY',
            ];

            $pdf = app('dompdf.wrapper');
            $pdf->loadView('reports.employee-physical-copy', $data);
            $pdf->save($pdfPath);

            // Create backup copy
            $backupPath = storage_path("app/backups/physical_copies/{$filename}");
            $this->ensureDirectoryExists(dirname($backupPath));
            File::copy($pdfPath, $backupPath);

            Log::info('Physical copy generated successfully', [
                'employee_id' => $employeeId,
                'employee_number' => $employee->employee_number,
                'filename' => $filename,
                'size_mb' => round(filesize($pdfPath) / 1024 / 1024, 2),
            ]);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $pdfPath,
                'backup_path' => $backupPath,
                'employee' => [
                    'id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'full_name' => "{$employee->first_name} {$employee->last_name}",
                ],
                'generated_at' => now(),
            ];

        } catch (\Exception $e) {
            Log::error('Physical copy generation failed', [
                'employee_id' => $employeeId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Schedule automated backups
     */
    public function scheduleAutomatedBackups(): void
    {
        // This would be called from Laravel's task scheduler
        Log::info('Running scheduled backup process');
        
        $options = [
            'automated' => true,
            'retention_check' => true,
        ];

        $result = $this->createFullBackup($options);
        
        if ($result['success']) {
            Log::info('Scheduled backup completed successfully', [
                'backup_id' => $result['backup_id'],
                'duration' => $result['duration'],
            ]);
        } else {
            Log::error('Scheduled backup failed', [
                'errors' => $result['errors'],
            ]);
        }
    }

    /**
     * Clean old backups based on retention policy
     */
    private function cleanOldBackups(): void
    {
        try {
            // Clean old database backups
            $databaseBackupPath = storage_path('app/backups/database');
            if (is_dir($databaseBackupPath)) {
                $this->cleanDirectoryByAge($databaseBackupPath, self::BACKUP_CONFIG['database']['retention_days']);
            }

            // Clean old files backups
            $filesBackupPath = storage_path('app/backups/files');
            if (is_dir($filesBackupPath)) {
                $this->cleanDirectoryByAge($filesBackupPath, self::BACKUP_CONFIG['files']['retention_days']);
            }

            Log::info('Old backups cleaned successfully');

        } catch (\Exception $e) {
            Log::error('Failed to clean old backups', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clean directory by file age
     */
    private function cleanDirectoryByAge(string $directory, int $retentionDays): void
    {
        $files = File::files($directory);
        $cutoffDate = now()->subDays($retentionDays);

        foreach ($files as $file) {
            $fileModifiedTime = Carbon::createFromTimestamp($file->getMTime());
            
            if ($fileModifiedTime->lt($cutoffDate)) {
                File::delete($file->getPathname());
                Log::info('Deleted old backup file', [
                    'file' => $file->getFilename(),
                    'modified_at' => $fileModifiedTime,
                ]);
            }
        }
    }

    /**
     * Verify database backup integrity
     */
    private function verifyDatabaseBackup(string $backupPath): array
    {
        try {
            if (str_ends_with($backupPath, '.gz')) {
                // For compressed backups, check if gzip file is valid
                $handle = gzopen($backupPath, 'rb');
                if (!$handle) {
                    return ['valid' => false, 'error' => 'Cannot open compressed backup'];
                }
                gzclose($handle);
                
                return ['valid' => true, 'type' => 'compressed'];
            } else {
                // For uncompressed backups, check SQL syntax
                $content = file_get_contents($backupPath, false, null, 0, 1024);
                if (strpos($content, '-- MySQL dump') === false && strpos($content, 'CREATE TABLE') === false) {
                    return ['valid' => false, 'error' => 'Invalid SQL backup format'];
                }
                
                return ['valid' => true, 'type' => 'uncompressed'];
            }

        } catch (\Exception $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate backup summary text
     */
    private function generateBackupSummary(string $backupId, array $backupResults): string
    {
        $summary = "E-LINGKOD DASOL HRIS - BACKUP SUMMARY\n";
        $summary .= "===================================\n\n";
        $summary .= "Backup ID: {$backupId}\n";
        $summary .= "Created: " . now()->format('Y-m-d H:i:s') . "\n";
        $summary .= "System: Municipality of Dasol, Pangasinan\n\n";

        if (isset($backupResults['database_backup'])) {
            $db = $backupResults['database_backup'];
            $summary .= "DATABASE BACKUP:\n";
            $summary .= "- Status: " . ($db['success'] ? 'SUCCESS' : 'FAILED') . "\n";
            if ($db['success']) {
                $summary .= "- File: {$db['filename']}\n";
                $summary .= "- Size: {$db['size_mb']} MB\n";
                $summary .= "- Compressed: " . ($db['compressed'] ? 'Yes' : 'No') . "\n";
            }
            $summary .= "\n";
        }

        if (isset($backupResults['files_backup'])) {
            $files = $backupResults['files_backup'];
            $summary .= "FILES BACKUP:\n";
            $summary .= "- Status: " . ($files['success'] ? 'SUCCESS' : 'FAILED') . "\n";
            if ($files['success']) {
                $summary .= "- File: {$files['filename']}\n";
                $summary .= "- Size: {$files['size_mb']} MB\n";
                $summary .= "- Total Files: {$files['total_files']}\n";
            }
            $summary .= "\n";
        }

        $summary .= "This backup contains sensitive government employee data.\n";
        $summary .= "Handle according to Republic Act 10173 (Data Privacy Act of 2012).\n";

        return $summary;
    }

    /**
     * Ensure directory exists
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }

    /**
     * Get backup statistics
     */
    public function getBackupStatistics(): array
    {
        try {
            $stats = [
                'database_backups' => $this->getDirectoryStats(storage_path('app/backups/database')),
                'files_backups' => $this->getDirectoryStats(storage_path('app/backups/files')),
                'physical_copies' => $this->getDirectoryStats(storage_path('app/physical_copies')),
                'total_backup_size_mb' => 0,
                'oldest_backup' => null,
                'newest_backup' => null,
            ];

            $stats['total_backup_size_mb'] = $stats['database_backups']['total_size_mb'] + 
                                           $stats['files_backups']['total_size_mb'] + 
                                           $stats['physical_copies']['total_size_mb'];

            return $stats;

        } catch (\Exception $e) {
            Log::error('Failed to get backup statistics', [
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get directory statistics
     */
    private function getDirectoryStats(string $directory): array
    {
        if (!is_dir($directory)) {
            return [
                'file_count' => 0,
                'total_size_mb' => 0,
                'oldest_file' => null,
                'newest_file' => null,
            ];
        }

        $files = File::files($directory);
        $totalSize = 0;
        $oldestTime = null;
        $newestTime = null;

        foreach ($files as $file) {
            $totalSize += $file->getSize();
            $modTime = Carbon::createFromTimestamp($file->getMTime());
            
            if (!$oldestTime || $modTime->lt($oldestTime)) {
                $oldestTime = $modTime;
            }
            
            if (!$newestTime || $modTime->gt($newestTime)) {
                $newestTime = $modTime;
            }
        }

        return [
            'file_count' => count($files),
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'oldest_file' => $oldestTime?->format('Y-m-d H:i:s'),
            'newest_file' => $newestTime?->format('Y-m-d H:i:s'),
        ];
    }
}