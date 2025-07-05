<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupSystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:system 
                            {--type=full : Type of backup (full|database|files)}
                            {--external : Copy to external drives}
                            {--stats : Show backup statistics}
                            {--employee= : Generate physical copy for specific employee ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create system backups and manage backup operations for E-Lingkod Dasol HRIS';

    private BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🛡️ E-Lingkod Dasol HRIS Backup System');
        $this->info('=====================================');

        try {
            if ($this->option('stats')) {
                return $this->showBackupStatistics();
            }

            if ($this->option('employee')) {
                return $this->generateEmployeePhysicalCopy();
            }

            return $this->performBackup();

        } catch (\Exception $e) {
            $this->error("Backup operation failed: {$e->getMessage()}");
            Log::error('Backup command failed', [
                'error' => $e->getMessage(),
                'options' => $this->options(),
            ]);
            return 1;
        }
    }

    /**
     * Perform system backup
     */
    private function performBackup(): int
    {
        $type = $this->option('type');
        $external = $this->option('external');

        $this->info("Starting {$type} backup...");
        
        $options = [
            'external_storage' => $external,
            'initiated_by' => 'console_command',
        ];

        $startTime = microtime(true);
        
        switch ($type) {
            case 'database':
                $result = $this->backupService->createDatabaseBackup(
                    'manual_' . now()->format('Y_m_d_H_i_s'),
                    $options
                );
                break;
                
            case 'files':
                $result = $this->backupService->createFilesBackup(
                    'manual_' . now()->format('Y_m_d_H_i_s'),
                    $options
                );
                break;
                
            case 'full':
            default:
                $result = $this->backupService->createFullBackup($options);
                break;
        }

        $duration = round(microtime(true) - $startTime, 2);

        if ($result['success']) {
            $this->info("✅ Backup completed successfully in {$duration} seconds");
            
            if ($type === 'full') {
                $this->displayFullBackupResults($result);
            } else {
                $this->displaySingleBackupResult($result, $type);
            }

            return 0;
        } else {
            $this->error('❌ Backup failed');
            if (!empty($result['errors'])) {
                foreach ($result['errors'] as $error) {
                    $this->error("   - {$error}");
                }
            }
            return 1;
        }
    }

    /**
     * Generate physical copy for employee
     */
    private function generateEmployeePhysicalCopy(): int
    {
        $employeeId = $this->option('employee');
        
        $this->info("Generating physical copy for Employee ID: {$employeeId}");
        
        $options = [
            'include_sensitive' => $this->confirm('Include sensitive information?', false),
            'watermark' => $this->ask('Watermark text', 'OFFICIAL COPY'),
        ];

        $result = $this->backupService->generatePhysicalCopy($employeeId, $options);

        if ($result['success']) {
            $this->info('✅ Physical copy generated successfully');
            $this->table(
                ['Field', 'Value'],
                [
                    ['Employee ID', $result['employee']['id']],
                    ['Employee Number', $result['employee']['employee_number']],
                    ['Full Name', $result['employee']['full_name']],
                    ['Filename', $result['filename']],
                    ['File Path', $result['path']],
                    ['Generated At', $result['generated_at']->format('Y-m-d H:i:s')],
                ]
            );
            return 0;
        } else {
            $this->error("❌ Physical copy generation failed: {$result['error']}");
            return 1;
        }
    }

    /**
     * Show backup statistics
     */
    private function showBackupStatistics(): int
    {
        $this->info('📊 Backup Statistics');
        $this->line('===================');

        $stats = $this->backupService->getBackupStatistics();

        if (isset($stats['error'])) {
            $this->error("Failed to get statistics: {$stats['error']}");
            return 1;
        }

        // Overall statistics
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Backup Size', $stats['total_backup_size_mb'] . ' MB'],
                ['Database Backups', $stats['database_backups']['file_count'] . ' files'],
                ['Files Backups', $stats['files_backups']['file_count'] . ' files'],
                ['Physical Copies', $stats['physical_copies']['file_count'] . ' files'],
            ]
        );

        // Database backups detail
        if ($stats['database_backups']['file_count'] > 0) {
            $this->info('Database Backups:');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Count', $stats['database_backups']['file_count']],
                    ['Total Size', $stats['database_backups']['total_size_mb'] . ' MB'],
                    ['Oldest', $stats['database_backups']['oldest_file'] ?? 'N/A'],
                    ['Newest', $stats['database_backups']['newest_file'] ?? 'N/A'],
                ]
            );
        }

        // Files backups detail
        if ($stats['files_backups']['file_count'] > 0) {
            $this->info('Files Backups:');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Count', $stats['files_backups']['file_count']],
                    ['Total Size', $stats['files_backups']['total_size_mb'] . ' MB'],
                    ['Oldest', $stats['files_backups']['oldest_file'] ?? 'N/A'],
                    ['Newest', $stats['files_backups']['newest_file'] ?? 'N/A'],
                ]
            );
        }

        return 0;
    }

    /**
     * Display full backup results
     */
    private function displayFullBackupResults(array $result): void
    {
        $this->info('Backup Results:');
        $this->line('==============');

        $this->table(
            ['Component', 'Status', 'Size (MB)', 'Details'],
            [
                [
                    'Database',
                    $result['database_backup']['success'] ? '✅ Success' : '❌ Failed',
                    $result['database_backup']['size_mb'] ?? 'N/A',
                    $result['database_backup']['filename'] ?? ($result['database_backup']['error'] ?? ''),
                ],
                [
                    'Files',
                    $result['files_backup']['success'] ? '✅ Success' : '❌ Failed',
                    $result['files_backup']['size_mb'] ?? 'N/A',
                    isset($result['files_backup']['total_files']) ? 
                        "{$result['files_backup']['total_files']} files" : 
                        ($result['files_backup']['error'] ?? ''),
                ],
            ]
        );

        // External storage results
        if (!empty($result['external_storage'])) {
            $this->info('External Storage:');
            $externalRows = [];
            foreach ($result['external_storage'] as $drive => $driveResult) {
                $externalRows[] = [
                    $drive,
                    $driveResult['success'] ? '✅ Success' : '❌ Failed',
                    $driveResult['success'] ? 
                        count($driveResult['copied_files']) . ' files copied' : 
                        $driveResult['error'],
                ];
            }
            $this->table(['Drive', 'Status', 'Details'], $externalRows);
        }
    }

    /**
     * Display single backup result
     */
    private function displaySingleBackupResult(array $result, string $type): void
    {
        $this->table(
            ['Field', 'Value'],
            [
                ['Type', ucfirst($type) . ' Backup'],
                ['Filename', $result['filename'] ?? 'N/A'],
                ['Size', ($result['size_mb'] ?? 0) . ' MB'],
                ['Files Count', $result['total_files'] ?? 'N/A'],
                ['Created At', $result['created_at']->format('Y-m-d H:i:s')],
            ]
        );
    }
}