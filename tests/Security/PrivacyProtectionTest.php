<?php

namespace Tests\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;
use App\Models\DocumentApprovalRequest;
use App\Models\LeaveApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

class PrivacyProtectionTest extends TestCase
{
    use RefreshDatabase;
    
    protected $employee;
    protected $hrAdmin;
    protected $superAdmin;
    protected $otherEmployee;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users with roles
        $this->employee = User::factory()->create();
        $this->employee->assignRole('Employee');
        
        $this->hrAdmin = User::factory()->create();
        $this->hrAdmin->assignRole('HR Admin');
        
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('Super Admin');
        
        $this->otherEmployee = User::factory()->create();
        $this->otherEmployee->assignRole('Employee');
    }
    
    /**
     * Test employee cannot access other employees' data
     */
    public function test_employee_cannot_access_other_employee_profiles()
    {
        $otherEmployeeProfile = Employee::factory()->create(['user_id' => $this->otherEmployee->id]);
        
        $response = $this->actingAs($this->employee)
            ->get("/employees/{$otherEmployeeProfile->id}");
        
        $response->assertStatus(403);
        
        // Check privacy violation is logged
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'privacy_violation',
            'causer_id' => $this->employee->id,
        ]);
    }
    
    /**
     * Test employee cannot access all employees list
     */
    public function test_employee_cannot_access_all_employees_list()
    {
        $response = $this->actingAs($this->employee)->get('/employees');
        
        $response->assertStatus(403);
        
        // Check privacy violation is logged
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'privacy_violation',
            'causer_id' => $this->employee->id,
        ]);
    }
    
    /**
     * Test employee cannot access other employees' document requests
     */
    public function test_employee_cannot_access_other_employee_documents()
    {
        $otherRequest = DocumentApprovalRequest::factory()->create([
            'employee_id' => $this->otherEmployee->employee->id
        ]);
        
        $response = $this->actingAs($this->employee)
            ->get("/document-approvals/{$otherRequest->id}");
        
        $response->assertStatus(403);
        
        // Check privacy violation is logged
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'privacy_violation',
            'causer_id' => $this->employee->id,
        ]);
    }
    
    /**
     * Test employee cannot access all document requests
     */
    public function test_employee_cannot_access_all_document_requests()
    {
        $response = $this->actingAs($this->employee)->get('/document-approvals');
        
        $response->assertRedirect('/document-approvals/my-requests');
        
        // Check privacy violation is logged
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'privacy_violation',
            'causer_id' => $this->employee->id,
        ]);
    }
    
    /**
     * Test employee can access own data
     */
    public function test_employee_can_access_own_profile()
    {
        $response = $this->actingAs($this->employee)
            ->get("/employees/{$this->employee->employee->id}");
        
        $response->assertStatus(200);
        $response->assertSee($this->employee->email);
        
        // Check access is logged (not as violation)
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'employee_access',
            'causer_id' => $this->employee->id,
        ]);
    }
    
    /**
     * Test employee can access own document requests
     */
    public function test_employee_can_access_own_document_requests()
    {
        $ownRequest = DocumentApprovalRequest::factory()->create([
            'employee_id' => $this->employee->employee->id
        ]);
        
        $response = $this->actingAs($this->employee)
            ->get("/document-approvals/{$ownRequest->id}");
        
        $response->assertStatus(200);
    }
    
    /**
     * Test HR Admin retains full access
     */
    public function test_hr_admin_can_access_all_employee_data()
    {
        Employee::factory()->count(5)->create();
        
        $response = $this->actingAs($this->hrAdmin)->get('/employees');
        
        $response->assertStatus(200);
        
        // Should see all employees
        $employees = Employee::all();
        foreach ($employees as $employee) {
            $response->assertSee($employee->full_name);
        }
    }
    
    /**
     * Test Super Admin retains full access
     */
    public function test_super_admin_can_access_all_data()
    {
        DocumentApprovalRequest::factory()->count(3)->create();
        
        $response = $this->actingAs($this->superAdmin)->get('/document-approvals');
        
        $response->assertStatus(200);
        
        // Should see all document requests
        $requests = DocumentApprovalRequest::all();
        $this->assertCount(3, $requests);
    }
    
    /**
     * Test employee cannot access leave applications of others
     */
    public function test_employee_cannot_access_other_employee_leave_applications()
    {
        $otherLeaveApplication = LeaveApplication::factory()->create([
            'employee_id' => $this->otherEmployee->employee->id
        ]);
        
        $response = $this->actingAs($this->employee)
            ->get("/leave_applications/{$otherLeaveApplication->id}");
        
        $response->assertStatus(403);
        
        // Check privacy violation is logged
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'privacy_violation',
            'causer_id' => $this->employee->id,
        ]);
    }
    
    /**
     * Test data minimization principle - employees only see necessary data
     */
    public function test_employee_api_returns_filtered_data()
    {
        // Create multiple employees and document requests
        Employee::factory()->count(5)->create();
        DocumentApprovalRequest::factory()->count(10)->create();
        
        // Create one request for our test employee
        DocumentApprovalRequest::factory()->create([
            'employee_id' => $this->employee->employee->id
        ]);
        
        $response = $this->actingAs($this->employee)
            ->getJson('/api/document-requests');
        
        $response->assertStatus(200);
        
        $data = $response->json();
        
        // Should only see own requests (1 request)
        $this->assertCount(1, $data['data']);
        $this->assertEquals($this->employee->employee->id, $data['data'][0]['employee_id']);
    }
    
    /**
     * Test audit trail captures privacy violations
     */
    public function test_privacy_violations_are_comprehensively_logged()
    {
        $otherEmployee = Employee::factory()->create();
        
        // Attempt multiple privacy violations
        $this->actingAs($this->employee)->get("/employees/{$otherEmployee->id}");
        $this->actingAs($this->employee)->get('/employees');
        $this->actingAs($this->employee)->get('/document-approvals');
        
        // Check all violations are logged with proper metadata
        $violations = Activity::where('log_name', 'privacy_violation')
            ->where('causer_id', $this->employee->id)
            ->get();
        
        $this->assertGreaterThanOrEqual(3, $violations->count());
        
        // Check that each violation has proper context
        foreach ($violations as $violation) {
            $this->assertNotNull($violation->description);
            $this->assertNotNull($violation->properties);
            $this->assertEquals($this->employee->id, $violation->causer_id);
        }
    }
    
    /**
     * Test session security - unauthorized direct URL access
     */
    public function test_direct_url_access_blocked_for_unauthorized_data()
    {
        $otherEmployee = Employee::factory()->create();
        
        // Test direct URL manipulation
        $unauthorizedUrls = [
            "/employees/{$otherEmployee->id}",
            "/employees/{$otherEmployee->id}/edit",
            "/employees",
            "/document-approvals",
            "/hr-analytics/dashboard",
        ];
        
        foreach ($unauthorizedUrls as $url) {
            $response = $this->actingAs($this->employee)->get($url);
            
            // Should either return 403 or redirect to authorized page
            $this->assertTrue(
                $response->status() === 403 || $response->isRedirect(),
                "URL {$url} should be blocked for employees"
            );
        }
    }
    
    /**
     * Test that employee role has minimal necessary permissions only
     */
    public function test_employee_role_has_minimal_permissions()
    {
        $permissions = $this->employee->getAllPermissions()->pluck('name')->toArray();
        
        // Should have only employee-specific permissions
        $allowedPermissions = [
            'employee.view-own',
            'employee.update-own',
            'leave.view-own',
            'leave.create',
            'document-approval.view-own',
            'document-approval.create',
            'performance.view-own',
        ];
        
        // Check that employee has only allowed permissions
        foreach ($permissions as $permission) {
            $this->assertContains($permission, $allowedPermissions, 
                "Employee should not have permission: {$permission}");
        }
        
        // Check that dangerous permissions are not present
        $dangerousPermissions = [
            'employee.view',
            'document-approval.view',
            'employee.create',
            'employee.delete',
            'user.manage',
        ];
        
        foreach ($dangerousPermissions as $dangerousPermission) {
            $this->assertNotContains($dangerousPermission, $permissions,
                "Employee should not have dangerous permission: {$dangerousPermission}");
        }
    }
}