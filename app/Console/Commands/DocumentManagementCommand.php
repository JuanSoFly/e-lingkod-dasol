<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DocumentManagementService;
use Illuminate\Console\Command;

class DocumentManagementCommand extends Command
{
    protected $signature = 'documents:manage 
                           {action : The action to perform (cleanup|auto-link|validate|stats|compliance)}
                           {--keep-versions=10 : Number of versions to keep during cleanup}
                           {--user-id=1 : User ID for auto-linking operations}';

    protected $description = 'Manage document versions and links';

    private DocumentManagementService $documentService;

    public function __construct(DocumentManagementService $documentService)
    {
        parent::__construct();
        $this->documentService = $documentService;
    }

    public function handle(): int
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'cleanup':
                return $this->handleCleanup();
            case 'auto-link':
                return $this->handleAutoLink();
            case 'validate':
                return $this->handleValidate();
            case 'stats':
                return $this->handleStats();
            case 'compliance':
                return $this->handleCompliance();
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    private function handleCleanup(): int
    {
        $keepVersions = (int) $this->option('keep-versions');
        
        $this->info("Starting document version cleanup (keeping {$keepVersions} versions per document)...");
        
        $results = $this->documentService->cleanupOldVersions($keepVersions);
        
        $this->info("Cleanup completed:");
        $this->line("- Documents processed: {$results['documents_processed']}");
        $this->line("- Versions cleaned: {$results['versions_cleaned']}");
        
        return 0;
    }

    private function handleAutoLink(): int
    {
        $userId = (int) $this->option('user-id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User with ID {$userId} not found");
            return 1;
        }
        
        $this->info("Starting automatic document linking...");
        
        $results = $this->documentService->processAutomaticLinking($user);
        
        $this->info("Auto-linking completed:");
        $this->line("- Documents processed: {$results['processed']}");
        $this->line("- Links created: {$results['links_created']}");
        
        if (!empty($results['errors'])) {
            $this->warn("Errors encountered:");
            foreach ($results['errors'] as $error) {
                $this->line("  Document {$error['document_id']}: {$error['error']}");
            }
        }
        
        return 0;
    }

    private function handleValidate(): int
    {
        $this->info("Validating document links...");
        
        // This would require extending the service to validate all employees
        // For now, we'll show stats instead
        $stats = $this->documentService->getDocumentStatistics();
        
        $this->info("Document link validation summary:");
        $this->line("- Total links: {$stats['total_links']}");
        $this->line("- Active links: {$stats['active_links']}");
        $this->line("- Links needing validation: {$stats['needs_validation']}");
        
        return 0;
    }

    private function handleStats(): int
    {
        $this->info("Gathering document management statistics...");
        
        $stats = $this->documentService->getDocumentStatistics();
        
        $this->info("Document Management Statistics:");
        $this->newLine();
        
        $this->line("Documents:");
        $this->line("- Total documents: {$stats['total_documents']}");
        $this->line("- Total versions: {$stats['total_versions']}");
        $this->line("- Pending versions: {$stats['pending_versions']}");
        $this->line("- Approved versions: {$stats['approved_versions']}");
        
        $this->newLine();
        $this->line("Links:");
        $this->line("- Total links: {$stats['total_links']}");
        $this->line("- Active links: {$stats['active_links']}");
        $this->line("- Automatic links: {$stats['automatic_links']}");
        $this->line("- Manual links: {$stats['manual_links']}");
        $this->line("- High confidence links: {$stats['high_confidence_links']}");
        $this->line("- Links needing validation: {$stats['needs_validation']}");
        
        // Storage usage
        $usage = $this->documentService->calculateStorageUsage();
        $this->newLine();
        $this->line("Storage:");
        $this->line("- Total files: {$usage['total_files']}");
        $this->line("- Total size: {$usage['total_size_human']}");
        
        return 0;
    }

    private function handleCompliance(): int
    {
        $this->info("Generating document compliance report...");
        
        $report = $this->documentService->generateComplianceReport();
        
        $this->info("Document Compliance Report:");
        $this->newLine();
        
        $this->line("Overview:");
        $this->line("- Total employees: {$report['total_employees']}");
        $this->line("- Employees with documents: {$report['employees_with_documents']}");
        $this->line("- Total documents: {$report['total_documents']}");
        $this->line("- Linked documents: {$report['linked_documents']}");
        $this->line("- Overall compliance rate: " . round($report['overall_compliance_rate'], 2) . "%");
        $this->line("- Average compliance rate: " . round($report['average_compliance_rate'], 2) . "%");
        
        if (!empty($report['low_compliance_employees'])) {
            $this->newLine();
            $this->warn("Employees with low compliance (< 50%):");
            
            foreach ($report['low_compliance_employees'] as $employee) {
                $this->line("- {$employee['employee_name']} (ID: {$employee['employee_id']}): " .
                           "{$employee['compliance_rate']}% ({$employee['linked_documents']}/{$employee['total_documents']} linked)");
            }
        }
        
        return 0;
    }
}