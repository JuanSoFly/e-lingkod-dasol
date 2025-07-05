<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class DocumentSearchControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $employeeUser;
    private User $departmentHeadUser;
    private Employee $testEmployee;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users with different roles
        $this->adminUser = User::factory()->create(['role' => 'hr_admin']);
        $this->employeeUser = User::factory()->create(['role' => 'employee']);
        $this->departmentHeadUser = User::factory()->create(['role' => 'department_head']);
        
        // Create test employee
        $this->testEmployee = Employee::factory()->create([
            'department' => 'IT',
            'employment_status' => 'active'
        ]);
        
        // Associate users with employees
        $this->employeeUser->employee()->associate($this->testEmployee);
        $this->employeeUser->save();
        
        $deptHeadEmployee = Employee::factory()->create([
            'department' => 'IT',
            'employment_status' => 'active'
        ]);
        $this->departmentHeadUser->employee()->associate($deptHeadEmployee);
        $this->departmentHeadUser->save();
    }

    /** @test */
    public function it_requires_authentication_for_search()
    {
        $response = $this->postJson('/api/documents/search');
        
        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_perform_basic_document_search()
    {
        EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'document_type' => 'id_card',
            'file_name' => 'test_document.pdf',
            'uploaded_by' => $this->adminUser->id
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/documents/search', [
                'search_term' => 'test',
                'per_page' => 10
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'documents',
                    'pagination' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total'
                    ],
                    'statistics',
                    'filters_applied',
                    'search_time'
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data.documents'));
    }

    /** @test */
    public function it_validates_search_criteria()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/documents/search', [
                'document_types' => ['invalid_type'],
                'per_page' => 150 // Exceeds max limit
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document_types.0', 'per_page']);
    }

    /** @test */
    public function it_can_filter_by_document_types()
    {
        EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'document_type' => 'id_card',
            'uploaded_by' => $this->adminUser->id
        ]);

        EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'document_type' => 'passport',
            'uploaded_by' => $this->adminUser->id
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/documents/search', [
                'document_types' => ['id_card'],
                'per_page' => 10
            ]);

        $response->assertStatus(200);
        $documents = $response->json('data.documents');
        
        $this->assertCount(1, $documents);
        $this->assertEquals('id_card', $documents[0]['document_type']);
    }

    /** @test */
    public function it_enforces_permission_based_access()
    {
        $otherEmployee = Employee::factory()->create(['department' => 'HR']);
        
        EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'file_name' => 'own_document.pdf',
            'uploaded_by' => $this->adminUser->id
        ]);

        EmployeeDocument::factory()->create([
            'employee_id' => $otherEmployee->id,
            'file_name' => 'other_document.pdf',
            'uploaded_by' => $this->adminUser->id
        ]);

        // Employee should only see their own documents
        $response = $this->actingAs($this->employeeUser)
            ->postJson('/api/documents/search', ['per_page' => 10]);

        $response->assertStatus(200);
        $documents = $response->json('data.documents');
        
        $this->assertCount(1, $documents);
        $this->assertEquals($this->testEmployee->id, $documents[0]['employee_id']);
    }

    /** @test */
    public function it_can_get_search_suggestions()
    {
        Employee::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'employee_number' => 'EMP001'
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/documents/suggestions?term=john&limit=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message'
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertIsArray($response->json('data'));
    }

    /** @test */
    public function it_validates_suggestion_parameters()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/documents/suggestions?term=a'); // Too short

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['term']);
    }

    /** @test */
    public function it_can_get_filter_options()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/documents/filter-options');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'document_types',
                    'departments',
                    'employment_statuses',
                    'mime_types',
                    'sort_options'
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
        
        $data = $response->json('data');
        $this->assertArrayHasKey('id_card', $data['document_types']);
        $this->assertArrayHasKey('active', $data['employment_statuses']);
    }

    /** @test */
    public function it_can_index_document_content()
    {
        $document = EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'uploaded_by' => $this->adminUser->id
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/documents/{$document->id}/index");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message'
            ]);

        $this->assertTrue($response->json('success'));
    }

    /** @test */
    public function it_prevents_unauthorized_document_indexing()
    {
        $otherEmployee = Employee::factory()->create(['department' => 'HR']);
        $document = EmployeeDocument::factory()->create([
            'employee_id' => $otherEmployee->id,
            'uploaded_by' => $this->adminUser->id
        ]);

        $response = $this->actingAs($this->employeeUser)
            ->postJson("/api/documents/{$document->id}/index");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_perform_bulk_indexing()
    {
        $documents = EmployeeDocument::factory()->count(3)->create([
            'employee_id' => $this->testEmployee->id,
            'uploaded_by' => $this->adminUser->id
        ]);

        $documentIds = $documents->pluck('id')->toArray();

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/documents/bulk-index', [
                'document_ids' => $documentIds
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'indexed',
                    'failed'
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
    }

    /** @test */
    public function it_restricts_bulk_operations_to_authorized_users()
    {
        $documents = EmployeeDocument::factory()->count(2)->create([
            'employee_id' => $this->testEmployee->id,
            'uploaded_by' => $this->adminUser->id
        ]);

        $response = $this->actingAs($this->employeeUser)
            ->postJson('/api/documents/bulk-index', [
                'document_ids' => $documents->pluck('id')->toArray()
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_validates_bulk_indexing_parameters()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/documents/bulk-index', [
                'document_ids' => [] // Empty array
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document_ids']);
    }

    /** @test */
    public function it_can_get_document_content()
    {
        $document = EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'uploaded_by' => $this->adminUser->id
        ]);

        // Mock cached content
        Cache::put("document_content_{$document->id}", 'test content', 60);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/documents/{$document->id}/content");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'document_id',
                    'content',
                    'content_length',
                    'has_content'
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('test content', $response->json('data.content'));
    }

    /** @test */
    public function it_can_clear_search_cache()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/documents/clear-cache');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message'
            ]);

        $this->assertTrue($response->json('success'));
    }

    /** @test */
    public function it_restricts_cache_clearing_to_authorized_users()
    {
        $response = $this->actingAs($this->employeeUser)
            ->postJson('/api/documents/clear-cache');

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_get_analytics()
    {
        EmployeeDocument::factory()->count(5)->create([
            'employee_id' => $this->testEmployee->id,
            'uploaded_by' => $this->adminUser->id,
            'uploaded_at' => now()->subDays(10)
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/documents/analytics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_documents',
                    'indexed_documents',
                    'indexing_coverage',
                    'document_types',
                    'departments',
                    'file_types',
                    'total_storage',
                    'average_file_size',
                    'upload_trends',
                    'search_performance'
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals(5, $response->json('data.total_documents'));
    }

    /** @test */
    public function it_can_filter_analytics_by_date_range()
    {
        EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'uploaded_by' => $this->adminUser->id,
            'uploaded_at' => now()->subDays(30)
        ]);

        EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'uploaded_by' => $this->adminUser->id,
            'uploaded_at' => now()->subDays(5)
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/documents/analytics?' . http_build_query([
                'date_from' => now()->subDays(10)->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d')
            ]));

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.total_documents'));
    }

    /** @test */
    public function it_restricts_analytics_to_authorized_users()
    {
        $response = $this->actingAs($this->employeeUser)
            ->getJson('/api/documents/analytics');

        $response->assertStatus(403);
    }

    /** @test */
    public function it_validates_analytics_parameters()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/documents/analytics?' . http_build_query([
                'date_from' => '2023-12-01',
                'date_to' => '2023-11-01' // date_to before date_from
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date_to']);
    }

    /** @test */
    public function it_handles_search_with_sorting()
    {
        EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'file_name' => 'alpha.pdf',
            'uploaded_by' => $this->adminUser->id,
            'uploaded_at' => now()->subDay()
        ]);

        EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'file_name' => 'beta.pdf',
            'uploaded_by' => $this->adminUser->id,
            'uploaded_at' => now()
        ]);

        // Sort by file name ascending
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/documents/search', [
                'sort_by' => 'file_name',
                'sort_direction' => 'asc',
                'per_page' => 10
            ]);

        $response->assertStatus(200);
        $documents = $response->json('data.documents');
        
        $this->assertCount(2, $documents);
        $this->assertEquals('alpha.pdf', $documents[0]['file_name']);
        $this->assertEquals('beta.pdf', $documents[1]['file_name']);
    }

    /** @test */
    public function it_handles_search_with_include_content_flag()
    {
        $document = EmployeeDocument::factory()->create([
            'employee_id' => $this->testEmployee->id,
            'uploaded_by' => $this->adminUser->id
        ]);

        Cache::put("document_content_{$document->id}", 'extracted content', 60);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/documents/search', [
                'include_content' => true,
                'per_page' => 10
            ]);

        $response->assertStatus(200);
        $documents = $response->json('data.documents');
        
        $this->assertCount(1, $documents);
        $this->assertArrayHasKey('extracted_content', $documents[0]);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}