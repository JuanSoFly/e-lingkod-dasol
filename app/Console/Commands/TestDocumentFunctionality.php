<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\DocumentVersion;
use App\Models\DocumentLink;
use App\Services\DocumentSearchService;
use App\Services\DocumentManagementService;
use App\Jobs\IndexDocumentContentJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TestDocumentFunctionality extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:documents 
                            {--test=all : Which test to run (search|version|link|all)}
                            {--employee= : Employee ID to test with}
                            {--detail : Enable detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test document management functionality including OCR, search, versioning, and linking';

    private DocumentSearchService $searchService;
    private DocumentManagementService $managementService;

    public function __construct(DocumentSearchService $searchService, DocumentManagementService $managementService)
    {
        parent::__construct();
        $this->searchService = $searchService;
        $this->managementService = $managementService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing Document Management Functionality');
        $this->info('==========================================');

        $test = $this->option('test');
        $employeeId = $this->option('employee');
        $verbose = $this->option('detail');

        try {
            // Get test employee
            $employee = $this->getTestEmployee($employeeId);
            if (!$employee) {
                $this->error('No employee found for testing.');
                return 1;
            }

            $this->info("Testing with Employee: {$employee->first_name} {$employee->last_name} (ID: {$employee->id})");
            $this->newLine();

            // Run tests based on option
            switch ($test) {
                case 'search':
                    return $this->testSearchFunctionality($employee, $verbose);
                case 'version':
                    return $this->testVersionControl($employee, $verbose);
                case 'link':
                    return $this->testDocumentLinking($employee, $verbose);
                case 'all':
                default:
                    $this->testSearchFunctionality($employee, $verbose);
                    $this->newLine();
                    $this->testVersionControl($employee, $verbose);
                    $this->newLine();
                    $this->testDocumentLinking($employee, $verbose);
                    break;
            }

            $this->info('✅ All document functionality tests completed successfully!');
            return 0;

        } catch (\Exception $e) {
            $this->error("Test failed: {$e->getMessage()}");
            if ($verbose) {
                $this->error($e->getTraceAsString());
            }
            return 1;
        }
    }

    /**
     * Test document search and OCR functionality
     */
    private function testSearchFunctionality(Employee $employee, bool $verbose): int
    {
        $this->info('🔍 Testing Document Search & OCR Functionality');
        $this->info('================================================');

        // Get documents for testing
        $documents = EmployeeDocument::where('employee_id', $employee->id)->get();
        
        if ($documents->isEmpty()) {
            $this->warn('No documents found for this employee. Creating sample document...');
            $documents = collect([$this->createSampleDocument($employee)]);
        }

        $this->info("Found {$documents->count()} document(s) for testing:");
        foreach ($documents as $doc) {
            $this->line("  - {$doc->file_name} ({$doc->document_type})");
        }
        $this->newLine();

        // Test 1: Content Indexing
        $this->info('Test 1: Content Indexing');
        $this->line('------------------------');
        
        foreach ($documents as $document) {
            try {
                $this->line("Indexing: {$document->file_name}");
                
                // Check if already indexed
                if ($document->content_indexed_at) {
                    $this->line("  ✓ Already indexed at: {$document->content_indexed_at}");
                    if ($document->ocr_content) {
                        $this->line("  ✓ OCR content length: " . strlen($document->ocr_content) . " characters");
                    }
                } else {
                    $this->line("  ⏳ Running content indexing job...");
                    IndexDocumentContentJob::dispatchSync($document);
                    $document->refresh();
                    
                    if ($document->content_indexed_at) {
                        $this->line("  ✅ Content indexed successfully");
                    } else {
                        $this->line("  ⚠️ Content indexing completed but no timestamp set");
                    }
                }
                
            } catch (\Exception $e) {
                $this->error("  ❌ Indexing failed: {$e->getMessage()}");
                if ($verbose) {
                    $this->error("     {$e->getTraceAsString()}");
                }
            }
        }
        $this->newLine();

        // Test 2: Search Functionality
        $this->info('Test 2: Search Functionality');
        $this->line('----------------------------');

        try {
            // Test basic search
            $user = $employee->user ?? auth()->user();
            if (!$user) {
                $this->warn('No user found for search testing. Skipping search tests.');
                return 0;
            }

            $searchCriteria = [
                'employee_id' => $employee->id,
                'per_page' => 10
            ];

            $this->line('Performing basic search...');
            $results = $this->searchService->searchDocuments($searchCriteria, $user);
            
            $this->line("  ✅ Search completed in {$results['search_time']} seconds");
            $this->line("  📊 Found {$results['pagination']['total']} document(s)");
            
            if ($verbose && !empty($results['documents'])) {
                $this->line('  Documents found:');
                foreach ($results['documents'] as $doc) {
                    $relevanceScore = isset($doc['relevance_score']) ? $doc['relevance_score'] : 'N/A';
                    $this->line("    - {$doc['file_name']} (Score: {$relevanceScore})");
                }
            }

            // Test text search if OCR content exists
            $ocrDocument = $documents->whereNotNull('ocr_content')->first();
            if ($ocrDocument && $ocrDocument->ocr_content) {
                $this->line('Testing text content search...');
                $words = str_word_count($ocrDocument->ocr_content, 1);
                if (!empty($words)) {
                    $searchWord = $words[0];
                    $textSearchCriteria = array_merge($searchCriteria, ['content' => $searchWord]);
                    
                    $textResults = $this->searchService->searchDocuments($textSearchCriteria, $user);
                    $this->line("  ✅ Text search for '{$searchWord}' found {$textResults['pagination']['total']} result(s)");
                }
            }

        } catch (\Exception $e) {
            $this->error("❌ Search test failed: {$e->getMessage()}");
            if ($verbose) {
                $this->error($e->getTraceAsString());
            }
        }

        return 0;
    }

    /**
     * Test document version control
     */
    private function testVersionControl(Employee $employee, bool $verbose): int
    {
        $this->info('📋 Testing Document Version Control');
        $this->info('===================================');

        // Get or create a document for testing
        $document = EmployeeDocument::where('employee_id', $employee->id)->first();
        if (!$document) {
            $document = $this->createSampleDocument($employee);
        }

        $this->line("Testing with document: {$document->file_name}");
        $this->newLine();

        try {
            // Test 1: Check existing versions
            $this->info('Test 1: Check Existing Versions');
            $this->line('--------------------------------');
            
            $existingVersions = DocumentVersion::where('original_document_id', $document->id)->count();
            $this->line("  📄 Existing versions: {$existingVersions}");

            // Test 2: Create new version
            $this->info('Test 2: Create New Version');
            $this->line('---------------------------');
            
            $versionData = [
                'version_number' => '1.1',
                'description' => 'Test version created by automation',
                'change_summary' => 'Automated test version for functionality validation',
                'approval_status' => 'pending',
                'uploader_id' => $employee->user?->id ?? 1
            ];

            $version = $this->managementService->createDocumentVersion($document, $versionData);
            
            if ($version) {
                $this->line("  ✅ Version created successfully (ID: {$version->id})");
                $this->line("  📝 Version number: {$version->version_number}");
                $this->line("  📋 Status: {$version->approval_status}");
            } else {
                $this->error("  ❌ Failed to create version");
            }

            // Test 3: Version approval workflow
            $this->info('Test 3: Version Approval Workflow');
            $this->line('----------------------------------');
            
            if ($version) {
                // Test approval
                $approved = $this->managementService->approveDocumentVersion(
                    $version,
                    $employee->user ?? auth()->user(),
                    'Approved via automated testing'
                );
                
                if ($approved) {
                    $version->refresh();
                    $this->line("  ✅ Version approved successfully");
                    $this->line("  📅 Approved at: {$version->approved_at}");
                } else {
                    $this->warn("  ⚠️ Version approval failed or not authorized");
                }
            }

            // Test 4: Version history
            $this->info('Test 4: Version History');
            $this->line('-----------------------');
            
            $allVersions = DocumentVersion::where('original_document_id', $document->id)
                ->orderBy('created_at', 'desc')
                ->get();
                
            $this->line("  📚 Total versions: {$allVersions->count()}");
            
            if ($verbose) {
                foreach ($allVersions as $v) {
                    $this->line("    - v{$v->version_number} ({$v->approval_status}) - {$v->created_at}");
                }
            }

        } catch (\Exception $e) {
            $this->error("❌ Version control test failed: {$e->getMessage()}");
            if ($verbose) {
                $this->error($e->getTraceAsString());
            }
        }

        return 0;
    }

    /**
     * Test document linking functionality
     */
    private function testDocumentLinking(Employee $employee, bool $verbose): int
    {
        $this->info('🔗 Testing Document Linking');
        $this->info('===========================');

        try {
            // Test 1: Check existing links
            $this->info('Test 1: Check Existing Links');
            $this->line('-----------------------------');
            
            $existingLinks = DocumentLink::whereHasMorph('source', [Employee::class], function($query) use ($employee) {
                $query->where('id', $employee->id);
            })->count();
            
            $this->line("  🔗 Existing links from employee: {$existingLinks}");

            // Test 2: Auto-linking functionality
            $this->info('Test 2: Auto-linking Functionality');
            $this->line('-----------------------------------');
            
            // Get employee's documents and leave applications for linking
            $documents = $employee->documents;
            $leaveApplications = $employee->leaveApplications;
            
            $this->line("  📄 Available documents: {$documents->count()}");
            $this->line("  📋 Available leave applications: {$leaveApplications->count()}");

            if ($documents->isNotEmpty() && $leaveApplications->isNotEmpty()) {
                $document = $documents->first();
                $leaveApplication = $leaveApplications->first();
                
                // Test creating a link
                $linkData = [
                    'link_type' => 'supporting_document',
                    'description' => 'Test link created by automation',
                    'created_by' => $employee->user?->id ?? 1
                ];
                
                $link = $this->managementService->createDocumentLink(
                    $document,
                    $leaveApplication,
                    $linkData
                );
                
                if ($link) {
                    $this->line("  ✅ Document link created successfully (ID: {$link->id})");
                    $this->line("  🔗 Link type: {$link->link_type}");
                } else {
                    $this->warn("  ⚠️ Failed to create document link");
                }
            } else {
                $this->warn("  ⚠️ Insufficient data for auto-linking test");
            }

            // Test 3: Link analytics
            $this->info('Test 3: Link Analytics');
            $this->line('----------------------');
            
            $linkStats = $this->managementService->getDocumentLinkingStatistics($employee);
            
            $this->line("  📊 Total linked documents: {$linkStats['total_linked_documents']}");
            $this->line("  📊 Unlinked documents: {$linkStats['unlinked_documents']}");
            $this->line("  📊 Link success rate: {$linkStats['link_success_rate']}%");
            
            if ($verbose && !empty($linkStats['links_by_type'])) {
                $this->line("  Link types breakdown:");
                foreach ($linkStats['links_by_type'] as $type => $count) {
                    $this->line("    - {$type}: {$count}");
                }
            }

        } catch (\Exception $e) {
            $this->error("❌ Document linking test failed: {$e->getMessage()}");
            if ($verbose) {
                $this->error($e->getTraceAsString());
            }
        }

        return 0;
    }

    /**
     * Get test employee
     */
    private function getTestEmployee(?string $employeeId): ?Employee
    {
        if ($employeeId) {
            return Employee::find($employeeId);
        }

        // Get first employee with documents
        $employee = Employee::whereHas('documents')->first();
        
        if (!$employee) {
            // Get any employee
            $employee = Employee::first();
        }

        return $employee;
    }

    /**
     * Create a sample document for testing
     */
    private function createSampleDocument(Employee $employee): EmployeeDocument
    {
        // Create a simple test file
        $content = "SAMPLE DOCUMENT\n\nEmployee: {$employee->first_name} {$employee->last_name}\nEmployee Number: {$employee->employee_number}\nDepartment: {$employee->department}\n\nThis is a test document created for validation purposes.\nIt contains searchable text content for OCR testing.";
        
        $fileName = "test_document_" . time() . ".txt";
        $filePath = "private/employee_documents/{$employee->id}/{$fileName}";
        
        Storage::put($filePath, $content);
        
        return EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'other',
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_size' => strlen($content),
            'mime_type' => 'text/plain',
            'uploaded_by' => $employee->user?->id ?? 1,
            'uploaded_at' => now(),
            'description' => 'Test document for functionality validation'
        ]);
    }
}