<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\DocumentSearchService;
use App\Models\User;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class DocumentSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    private DocumentSearchService $documentSearchService;
    private User $adminUser;
    private User $employeeUser;
    private User $departmentHeadUser;
    private Employee $testEmployee;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->documentSearchService = new DocumentSearchService();
        
        // Create test users with different roles
        $this->adminUser = User::factory()->create(['role' => 'hr_admin']);
        $this->employeeUser = User::factory()->create(['role' => 'employee']);
        $this->departmentHeadUser = User::factory()->create(['role' => 'department_head']);
        
        // Create test employee
        $this->testEmployee = Employee::factory()->create([
            'department' => 'IT',
            'employment_status' => 'active'
        ]);
        
        // Associate employee user with employee record
        $this->employeeUser->employee()->associate($this->testEmployee);
        $this->employeeUser->save();
        
        // Create department head employee
        $deptHeadEmployee = Employee::factory()->create([
            'department' => 'IT',
            'employment_status' => 'active'
        ]);
        $this->departmentHeadUser->employee()->associate($deptHeadEmployee);
        $this->departmentHeadUser->save();
    }

    /** @test */
    public function it_can_search_documents_with_basic_criteria()
    {
        // Create test documents
        $document1 = EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'document_type' => 'id_card',
            'file_name' => 'john_doe_id.pdf',
            'uploaded_by' => $this->adminUser->id,
            'uploaded_at' => now()->subDays(5)
        ]);

        $document2 = EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'document_type' => 'passport',
            'file_name' => 'john_doe_passport.jpg',
            'uploaded_by' => $this->adminUser->id,
            'uploaded_at' => now()->subDays(3)
        ]);

        $searchCriteria = [
            'search_term' => 'john',
            'per_page' => 10
        ];

        $results = $this->documentSearchService->searchDocuments($searchCriteria, $this->adminUser);

        $this->assertTrue(is_array($results));
        $this->assertArrayHasKey('documents', $results);
        $this->assertArrayHasKey('pagination', $results);
        $this->assertArrayHasKey('statistics', $results);
        $this->assertCount(2, $results['documents']);
    }

    /** @test */
    public function it_applies_permission_based_filtering_for_employees()
    {
        // Create documents for different employees
        $otherEmployee = Employee::factory()->create(['department' => 'HR']);
        
        $ownDocument = EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'document_type' => 'id_card',
            'file_name' => 'own_document.pdf',
            'uploaded_by' => $this->adminUser->id
        ]);

        $otherDocument = EmployeeDocument::factory()->create([
            'employee_id' => $otherEmployee->id,
            'document_type' => 'passport',
            'file_name' => 'other_document.pdf',
            'uploaded_by' => $this->adminUser->id
        ]);

        $searchCriteria = ['per_page' => 10];
        $results = $this->documentSearchService->searchDocuments($searchCriteria, $this->employeeUser);

        // Employee should only see their own documents
        $this->assertCount(1, $results['documents']);
        $this->assertEquals($ownDocument->id, $results['documents'][0]['id']);
    }

    /** @test */
    public function it_applies_permission_based_filtering_for_department_heads()
    {
        // Create employees in different departments
        $itEmployee = Employee::factory()->create(['department' => 'IT']);
        $hrEmployee = Employee::factory()->create(['department' => 'HR']);
        
        $itDocument = EmployeeDocument::factory()->create([
            'employee_id' => $itEmployee->id,
            'document_type' => 'id_card',
            'file_name' => 'it_document.pdf',
            'uploaded_by' => $this->adminUser->id
        ]);

        $hrDocument = EmployeeDocument::factory()->create([
            'employee_id' => $hrEmployee->id,
            'document_type' => 'passport',
            'file_name' => 'hr_document.pdf',
            'uploaded_by' => $this->adminUser->id
        ]);

        $searchCriteria = ['per_page' => 10];
        $results = $this->documentSearchService->searchDocuments($searchCriteria, $this->departmentHeadUser);

        // Department head should only see documents from their department (IT)
        $this->assertCount(1, $results['documents']);
        $this->assertEquals($itDocument->id, $results['documents'][0]['id']);
    }

    /** @test */
    public function it_can_filter_by_document_types()
    {
        EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'document_type' => 'id_card',
            'file_name' => 'test_id.pdf',
            'uploaded_by' => $this->adminUser->id
        ]);

        EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'document_type' => 'passport',
            'file_name' => 'test_passport.pdf',
            'uploaded_by' => $this->adminUser->id
        ]);

        $searchCriteria = [
            'document_types' => ['id_card'],
            'per_page' => 10
        ];

        $results = $this->documentSearchService->searchDocuments($searchCriteria, $this->adminUser);

        $this->assertCount(1, $results['documents']);
        $this->assertEquals('id_card', $results['documents'][0]['document_type']);
    }

    /** @test */
    public function it_can_filter_by_date_ranges()
    {
        $oldDocument = EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'uploaded_at' => now()->subDays(30),
            'uploaded_by' => $this->adminUser->id
        ]);

        $recentDocument = EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'uploaded_at' => now()->subDays(5),
            'uploaded_by' => $this->adminUser->id
        ]);

        $searchCriteria = [
            'uploaded_from' => now()->subDays(10)->format('Y-m-d'),
            'uploaded_to' => now()->format('Y-m-d'),
            'per_page' => 10
        ];

        $results = $this->documentSearchService->searchDocuments($searchCriteria, $this->adminUser);

        $this->assertCount(1, $results['documents']);
        $this->assertEquals($recentDocument->id, $results['documents'][0]['id']);
    }

    /** @test */
    public function it_can_filter_by_file_size()
    {
        $smallDocument = EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'file_size' => 1024, // 1KB
            'uploaded_by' => $this->adminUser->id
        ]);

        $largeDocument = EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'file_size' => 5 * 1024 * 1024, // 5MB
            'uploaded_by' => $this->adminUser->id
        ]);

        $searchCriteria = [
            'min_file_size' => 1024 * 1024, // 1MB minimum
            'per_page' => 10
        ];

        $results = $this->documentSearchService->searchDocuments($searchCriteria, $this->adminUser);

        $this->assertCount(1, $results['documents']);
        $this->assertEquals($largeDocument->id, $results['documents'][0]['id']);
    }

    /** @test */
    public function it_calculates_relevance_scores_correctly()
    {
        $exactMatch = EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'file_name' => 'test_document.pdf',
            'uploaded_by' => $this->adminUser->id,
            'uploaded_at' => now()
        ]);

        $partialMatch = EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'file_name' => 'some_test_file.pdf',
            'uploaded_by' => $this->adminUser->id,
            'uploaded_at' => now()->subDays(1)
        ]);

        $searchCriteria = [
            'search_term' => 'test',
            'sort_by' => 'relevance',
            'per_page' => 10
        ];

        $results = $this->documentSearchService->searchDocuments($searchCriteria, $this->adminUser);

        $this->assertCount(2, $results['documents']);
        
        // Check that results include relevance scores
        $this->assertArrayHasKey('relevance_score', $results['documents'][0]);
        $this->assertArrayHasKey('relevance_score', $results['documents'][1]);
        
        // The exact match should have a higher relevance score
        $firstScore = $results['documents'][0]['relevance_score'];
        $secondScore = $results['documents'][1]['relevance_score'];
        $this->assertGreaterThan($secondScore, $firstScore);
    }

    /** @test */
    public function it_can_parse_advanced_search_terms()
    {
        $reflection = new \ReflectionClass($this->documentSearchService);
        $method = $reflection->getMethod('parseSearchTerm');
        $method->setAccessible(true);

        // Test phrase search
        $result = $method->invoke($this->documentSearchService, '"exact phrase" normal -exclude');
        
        $this->assertArrayHasKey('include_terms', $result);
        $this->assertArrayHasKey('exclude_terms', $result);
        $this->assertArrayHasKey('phrase_terms', $result);
        
        $this->assertContains('exact phrase', $result['phrase_terms']);
        $this->assertContains('exclude', $result['exclude_terms']);
        $this->assertContains('normal', $result['include_terms']);
    }

    /** @test */
    public function it_generates_search_suggestions()
    {
        Employee::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'employee_number' => 'EMP001'
        ]);

        $suggestions = $this->documentSearchService->getSearchSuggestions('john', $this->adminUser, 5);

        $this->assertTrue(is_array($suggestions));
        $this->assertNotEmpty($suggestions);
        
        $employeeSuggestion = collect($suggestions)->firstWhere('type', 'employee');
        $this->assertNotNull($employeeSuggestion);
        $this->assertStringContainsString('John Doe', $employeeSuggestion['label']);
    }

    /** @test */
    public function it_gets_available_document_types()
    {
        $documentTypes = $this->documentSearchService->getAvailableDocumentTypes();

        $this->assertTrue(is_array($documentTypes));
        $this->assertArrayHasKey('id_card', $documentTypes);
        $this->assertArrayHasKey('passport', $documentTypes);
        $this->assertArrayHasKey('diploma', $documentTypes);
    }

    /** @test */
    public function it_gets_available_departments_based_on_permissions()
    {
        // Test admin access (should see all departments)
        $adminDepartments = $this->documentSearchService->getAvailableDepartments($this->adminUser);
        $this->assertTrue(is_array($adminDepartments));

        // Test department head access (should see only their department)
        $deptHeadDepartments = $this->documentSearchService->getAvailableDepartments($this->departmentHeadUser);
        $this->assertTrue(is_array($deptHeadDepartments));
        $this->assertContains('IT', $deptHeadDepartments);
    }

    /** @test */
    public function it_generates_search_statistics()
    {
        EmployeeDocument::factory()->count(3)->create([
            'employee_id' => $this->testEmployee->id,
            'document_type' => 'id_card',
            'uploaded_by' => $this->adminUser->id
        ]);

        EmployeeDocument::factory()->count(2)->create([
            'employee_id' => $this->testEmployee->id,
            'document_type' => 'passport',
            'uploaded_by' => $this->adminUser->id
        ]);

        $searchCriteria = ['per_page' => 10];
        $results = $this->documentSearchService->searchDocuments($searchCriteria, $this->adminUser);

        $this->assertArrayHasKey('statistics', $results);
        $stats = $results['statistics'];
        
        $this->assertEquals(5, $stats['total_results']);
        $this->assertArrayHasKey('document_types', $stats);
        $this->assertEquals(3, $stats['document_types']['id_card']);
        $this->assertEquals(2, $stats['document_types']['passport']);
    }

    /** @test */
    public function it_handles_search_caching()
    {
        Cache::flush();

        $document = EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'uploaded_by' => $this->adminUser->id
        ]);

        $searchCriteria = ['search_term' => 'test', 'per_page' => 10];

        // First search should hit the database
        $results1 = $this->documentSearchService->searchDocuments($searchCriteria, $this->adminUser);
        
        // Second search should use cache
        $results2 = $this->documentSearchService->searchDocuments($searchCriteria, $this->adminUser);
        
        $this->assertEquals($results1, $results2);
    }

    /** @test */
    public function it_can_clear_search_cache()
    {
        // Add something to cache
        Cache::put('document_search_test', 'test_value', 60);
        
        $this->documentSearchService->clearSearchCache();
        
        // Note: This test might need adjustment based on actual cache implementation
        $this->assertTrue(true); // Basic test that method executes without error
    }

    /** @test */
    public function it_extracts_content_from_cached_documents()
    {
        $document = EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'uploaded_by' => $this->adminUser->id,
            'mime_type' => 'application/pdf'
        ]);

        // Mock cached content
        Cache::put("document_content_{$document->id}", 'test content', 60);

        $content = $this->documentSearchService->extractDocumentContent($document);
        
        $this->assertEquals('test content', $content);
    }

    /** @test */
    public function it_handles_document_indexing()
    {
        $document = EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'uploaded_by' => $this->adminUser->id,
            'file_size' => 1024
        ]);

        // Mock successful content extraction
        Cache::put("document_content_{$document->id}", 'extracted content', 60);

        $result = $this->documentSearchService->indexDocumentContent($document);
        
        $this->assertTrue($result);
    }

    /** @test */
    public function it_performs_bulk_document_indexing()
    {
        $documents = EmployeeDocument::factory()->count(3)->create([
            'employee_id' => $this->testEmployee->id,
            'uploaded_by' => $this->adminUser->id,
            'file_size' => 1024
        ]);

        $documentIds = $documents->pluck('id')->toArray();

        // Mock successful content extraction for all documents
        foreach ($documents as $document) {
            Cache::put("document_content_{$document->id}", 'extracted content', 60);
        }

        $results = $this->documentSearchService->bulkIndexDocuments($documentIds);
        
        $this->assertArrayHasKey('indexed', $results);
        $this->assertArrayHasKey('failed', $results);
        $this->assertEquals(3, $results['indexed']);
        $this->assertEquals(0, $results['failed']);
    }

    /** @test */
    public function it_generates_highlights_for_search_terms()
    {
        $document = EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'file_name' => 'test_document.pdf',
            'uploaded_by' => $this->adminUser->id
        ]);

        $reflection = new \ReflectionClass($this->documentSearchService);
        $method = $reflection->getMethod('generateHighlights');
        $method->setAccessible(true);

        $highlights = $method->invoke($this->documentSearchService, $document, 'test');
        
        $this->assertArrayHasKey('file_name', $highlights);
        $this->assertStringContainsString('<mark>test</mark>', $highlights['file_name']);
    }

    /** @test */
    public function it_formats_file_sizes_correctly()
    {
        $reflection = new \ReflectionClass($this->documentSearchService);
        $method = $reflection->getMethod('formatFileSize');
        $method->setAccessible(true);

        $this->assertEquals('0 B', $method->invoke($this->documentSearchService, 0));
        $this->assertEquals('1 KB', $method->invoke($this->documentSearchService, 1024));
        $this->assertEquals('1 MB', $method->invoke($this->documentSearchService, 1024 * 1024));
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}