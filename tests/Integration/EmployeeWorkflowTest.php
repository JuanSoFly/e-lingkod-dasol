<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;
use App\Models\DocumentApprovalRequest;
use App\Models\LeaveApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

class EmployeeWorkflowTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Test complete employee self-service workflow
     */
    public function test_employee_can_complete_self_service_workflow()
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        // Employee logs in
        $this->actingAs($employee);
        
        // Access dashboard (should work)
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        
        // View own profile (should work)
        $response = $this->get("/employees/{$employee->employee->id}");
        $response->assertStatus(200);
        
        // Create document request (should work)
        $response = $this->post('/document-approvals', [
            'type' => 'certificate_of_employment',
            'purpose' => 'Bank loan application',
            'notes' => 'Urgent request',
        ]);
        $response->assertRedirect();
        
        // View own requests (should work)
        $response = $this->get('/document-approvals/my-requests');
        $response->assertStatus(200);
        
        // Try to access other employee's data (should fail)
        $otherEmployee = Employee::factory()->create();
        $response = $this->get("/employees/{$otherEmployee->id}");
        $response->assertStatus(403);
        
        // Try to access all employees (should fail)
        $response = $this->get('/employees');
        $response->assertStatus(403);
        
        // Verify all actions are logged
        $this->assertDatabaseHas('activity_log', [
            'causer_id' => $employee->id,
            'log_name' => 'employee_access',
        ]);
        
        $this->assertDatabaseHas('activity_log', [
            'causer_id' => $employee->id,
            'log_name' => 'privacy_violation',
        ]);
    }
    
    /**
     * Test HR Admin complete workflow
     */
    public function test_hr_admin_can_complete_admin_workflow()
    {
        $hrAdmin = User::factory()->create();
        $hrAdmin->assignRole('HR Admin');
        
        // Create test data
        Employee::factory()->count(5)->create();
        DocumentApprovalRequest::factory()->count(10)->create();
        
        $this->actingAs($hrAdmin);
        
        // Access all employees (should work)
        $response = $this->get('/employees');
        $response->assertStatus(200);
        
        // Access all document requests (should work)
        $response = $this->get('/document-approvals');
        $response->assertStatus(200);
        
        // Approve a document request (should work)
        $request = DocumentApprovalRequest::first();
        $response = $this->patch("/document-approvals/{$request->id}/approve");
        $response->assertRedirect();
        
        // View HR analytics (should work)
        $response = $this->get('/hr-analytics/dashboard');
        $response->assertStatus(200);
        
        // All actions should be logged
        $this->assertDatabaseHas('activity_log', [
            'causer_id' => $hrAdmin->id,
        ]);
    }
    
    /**
     * Test Super Admin complete workflow
     */
    public function test_super_admin_can_complete_super_admin_workflow()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');
        
        $this->actingAs($superAdmin);
        
        // Access all system functions (should work)
        $response = $this->get('/employees');
        $response->assertStatus(200);
        
        $response = $this->get('/document-approvals');
        $response->assertStatus(200);
        
        $response = $this->get('/hr-analytics/dashboard');
        $response->assertStatus(200);
        
        // All actions should be logged
        $this->assertDatabaseHas('activity_log', [
            'causer_id' => $superAdmin->id,
        ]);
    }
    
    /**
     * Test end-to-end document approval workflow with security
     */
    public function test_document_approval_workflow_maintains_security()
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        $hrAdmin = User::factory()->create();
        $hrAdmin->assignRole('HR Admin');
        
        // Step 1: Employee creates document request
        $this->actingAs($employee);
        
        $response = $this->post('/document-approvals', [
            'type' => 'certificate_of_employment',
            'purpose' => 'Bank loan application',
            'notes' => 'Urgent request',
        ]);
        
        $response->assertRedirect();
        
        $documentRequest = DocumentApprovalRequest::where('employee_id', $employee->employee->id)->first();
        $this->assertNotNull($documentRequest);
        
        // Step 2: Employee can view their own request
        $response = $this->get("/document-approvals/{$documentRequest->id}");
        $response->assertStatus(200);
        
        // Step 3: Employee cannot view other employees' requests
        $otherRequest = DocumentApprovalRequest::factory()->create();
        $response = $this->get("/document-approvals/{$otherRequest->id}");
        $response->assertStatus(403);
        
        // Step 4: HR Admin can view and approve the request
        $this->actingAs($hrAdmin);
        
        $response = $this->get("/document-approvals/{$documentRequest->id}");
        $response->assertStatus(200);
        
        $response = $this->patch("/document-approvals/{$documentRequest->id}/approve");
        $response->assertRedirect();
        
        // Step 5: Verify approval is logged
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => DocumentApprovalRequest::class,
            'subject_id' => $documentRequest->id,
            'causer_id' => $hrAdmin->id,
        ]);
        
        // Step 6: Employee can see the approved status
        $this->actingAs($employee);
        
        $response = $this->get("/document-approvals/{$documentRequest->id}");
        $response->assertStatus(200);
        $response->assertSee('approved'); // Assuming status is displayed
    }
    
    /**
     * Test leave application workflow with security
     */
    public function test_leave_application_workflow_maintains_security()
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        $otherEmployee = User::factory()->create();
        $otherEmployee->assignRole('Employee');
        
        $hrAdmin = User::factory()->create();
        $hrAdmin->assignRole('HR Admin');
        
        // Step 1: Employee applies for leave
        $this->actingAs($employee);
        
        $response = $this->post('/leave_applications', [
            'leave_type_id' => 1,
            'start_date' => '2025-08-01',
            'end_date' => '2025-08-05',
            'reason' => 'Family vacation',
        ]);
        
        // Step 2: Employee can view their own leave applications
        $response = $this->get('/leave_applications');
        $response->assertStatus(200);
        
        // Step 3: Employee cannot view other employees' leave applications
        $otherLeave = LeaveApplication::factory()->create([
            'employee_id' => $otherEmployee->employee->id
        ]);
        
        $response = $this->get("/leave_applications/{$otherLeave->id}");
        $response->assertStatus(403);
        
        // Step 4: HR Admin can view all leave applications
        $this->actingAs($hrAdmin);
        
        $response = $this->get('/leave_applications');
        $response->assertStatus(200);
        
        // All interactions should be logged
        $this->assertGreaterThan(0, Activity::count());
    }
    
    /**
     * Test employee profile update workflow with security
     */
    public function test_employee_profile_update_workflow_security()
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        $otherEmployee = User::factory()->create();
        $otherEmployee->assignRole('Employee');
        
        $this->actingAs($employee);
        
        // Employee can update their own profile
        $response = $this->get("/employees/{$employee->employee->id}/edit");
        $response->assertStatus(200);
        
        $response = $this->put("/employees/{$employee->employee->id}", [
            'contact_number' => '09123456789',
            'address' => 'Updated Address',
        ]);
        $response->assertRedirect();
        
        // Employee cannot update other employee's profile
        $response = $this->get("/employees/{$otherEmployee->employee->id}/edit");
        $response->assertStatus(403);
        
        $response = $this->put("/employees/{$otherEmployee->employee->id}", [
            'contact_number' => '09987654321',
        ]);
        $response->assertStatus(403);
        
        // Updates should be logged
        $this->assertDatabaseHas('activity_log', [
            'causer_id' => $employee->id,
            'subject_type' => Employee::class,
            'subject_id' => $employee->employee->id,
        ]);
    }
    
    /**
     * Test cross-role data isolation
     */
    public function test_cross_role_data_isolation()
    {
        // Create users with different roles
        $employee1 = User::factory()->create();
        $employee1->assignRole('Employee');
        
        $employee2 = User::factory()->create();
        $employee2->assignRole('Employee');
        
        $hrAdmin = User::factory()->create();
        $hrAdmin->assignRole('HR Admin');
        
        // Create data for each employee
        $request1 = DocumentApprovalRequest::factory()->create([
            'employee_id' => $employee1->employee->id
        ]);
        
        $request2 = DocumentApprovalRequest::factory()->create([
            'employee_id' => $employee2->employee->id
        ]);
        
        // Test Employee 1 isolation
        $this->actingAs($employee1);
        
        $response = $this->get('/document-approvals/my-requests');
        $response->assertStatus(200);
        $response->assertSee($request1->id);
        $response->assertDontSee($request2->id);
        
        // Test Employee 2 isolation
        $this->actingAs($employee2);
        
        $response = $this->get('/document-approvals/my-requests');
        $response->assertStatus(200);
        $response->assertSee($request2->id);
        $response->assertDontSee($request1->id);
        
        // Test HR Admin can see all
        $this->actingAs($hrAdmin);
        
        $response = $this->get('/document-approvals');
        $response->assertStatus(200);
        $response->assertSee($request1->id);
        $response->assertSee($request2->id);
    }
    
    /**
     * Test system behavior under concurrent access
     */
    public function test_concurrent_access_security()
    {
        // Create multiple employees
        $employees = [];
        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->create();
            $user->assignRole('Employee');
            $employees[] = $user;
        }
        
        // Simulate concurrent access
        foreach ($employees as $employee) {
            $this->actingAs($employee);
            
            // Each employee should only see their own data
            $response = $this->get("/employees/{$employee->employee->id}");
            $response->assertStatus(200);
            
            // Each employee should be blocked from seeing others' data
            foreach ($employees as $otherEmployee) {
                if ($otherEmployee->id !== $employee->id) {
                    $response = $this->get("/employees/{$otherEmployee->employee->id}");
                    $response->assertStatus(403);
                }
            }
        }
        
        // All privacy violations should be logged
        $violations = Activity::where('log_name', 'privacy_violation')->count();
        $this->assertGreaterThan(0, $violations);
    }
    
    /**
     * Test audit trail completeness across workflow
     */
    public function test_audit_trail_completeness()
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        $hrAdmin = User::factory()->create();
        $hrAdmin->assignRole('HR Admin');
        
        // Clear existing logs
        Activity::truncate();
        
        // Employee performs various actions
        $this->actingAs($employee);
        
        $this->get('/dashboard');
        $this->get("/employees/{$employee->employee->id}");
        $this->post('/document-approvals', [
            'type' => 'certificate_of_employment',
            'purpose' => 'Bank loan application',
        ]);
        $this->get('/document-approvals/my-requests');
        
        // Attempt unauthorized access
        $otherEmployee = Employee::factory()->create();
        $this->get("/employees/{$otherEmployee->id}");
        
        // HR Admin performs actions
        $this->actingAs($hrAdmin);
        
        $this->get('/employees');
        $this->get('/document-approvals');
        
        // Verify all actions are logged
        $logs = Activity::all();
        
        $this->assertGreaterThan(5, $logs->count(), 'All actions should be logged');
        
        // Verify different log types exist
        $logNames = $logs->pluck('log_name')->unique();
        $this->assertContains('employee_access', $logNames);
        $this->assertContains('privacy_violation', $logNames);
        
        // Verify each log has proper metadata
        foreach ($logs as $log) {
            $this->assertNotNull($log->causer_id);
            $this->assertNotNull($log->description);
            $this->assertInstanceOf('Carbon\Carbon', $log->created_at);
        }
    }
    
    /**
     * Test system recovery after security incident
     */
    public function test_system_recovery_after_security_incident()
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        $hrAdmin = User::factory()->create();
        $hrAdmin->assignRole('HR Admin');
        
        // Simulate security incident (multiple unauthorized access attempts)
        $this->actingAs($employee);
        
        for ($i = 0; $i < 10; $i++) {
            $otherEmployee = Employee::factory()->create();
            $this->get("/employees/{$otherEmployee->id}");
        }
        
        // System should continue to work normally
        $response = $this->get("/employees/{$employee->employee->id}");
        $response->assertStatus(200);
        
        // HR Admin should still have full access
        $this->actingAs($hrAdmin);
        
        $response = $this->get('/employees');
        $response->assertStatus(200);
        
        // All violations should be logged
        $violations = Activity::where('log_name', 'privacy_violation')->count();
        $this->assertEquals(10, $violations);
    }
}