<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class LeaveApplicationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles and permissions
        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Employee']);
        Role::create(['name' => 'Supervisor']);
        
        Permission::create(['name' => 'leave.view']);
        Permission::create(['name' => 'leave.create']);
        Permission::create(['name' => 'leave.approve']);
    }

    public function test_employee_can_create_leave_application()
    {
        $employee = Employee::factory()->create();
        $user = $employee->user;
        $user->assignRole('Employee');
        $user->givePermissionTo('leave.create');
        
        $leaveType = LeaveType::factory()->create();
        
        $leaveData = [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2024-12-01',
            'end_date' => '2024-12-03',
            'reason' => 'Personal matters',
            'days_requested' => 3,
        ];
        
        $response = $this->actingAs($user)
            ->post(route('leave-applications.store'), $leaveData);
            
        $response->assertRedirect();
        $this->assertDatabaseHas('leave_applications', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'reason' => 'Personal matters',
            'status' => 'pending',
        ]);
    }

    public function test_leave_application_requires_valid_leave_type()
    {
        $employee = Employee::factory()->create();
        $user = $employee->user;
        $user->assignRole('Employee');
        $user->givePermissionTo('leave.create');
        
        $leaveData = [
            'leave_type_id' => 999, // Non-existent leave type
            'start_date' => '2024-12-01',
            'end_date' => '2024-12-03',
            'reason' => 'Personal matters',
            'days_requested' => 3,
        ];
        
        $response = $this->actingAs($user)
            ->post(route('leave-applications.store'), $leaveData);
            
        $response->assertSessionHasErrors(['leave_type_id']);
    }

    public function test_leave_application_validates_date_ranges()
    {
        $employee = Employee::factory()->create();
        $user = $employee->user;
        $user->assignRole('Employee');
        $user->givePermissionTo('leave.create');
        
        $leaveType = LeaveType::factory()->create();
        
        $leaveData = [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2024-12-03',
            'end_date' => '2024-12-01', // End date before start date
            'reason' => 'Personal matters',
            'days_requested' => 3,
        ];
        
        $response = $this->actingAs($user)
            ->post(route('leave-applications.store'), $leaveData);
            
        $response->assertSessionHasErrors(['end_date']);
    }

    public function test_employee_can_only_view_own_applications()
    {
        $employee1 = Employee::factory()->create();
        $employee2 = Employee::factory()->create();
        
        $user1 = $employee1->user;
        $user1->assignRole('Employee');
        $user1->givePermissionTo('leave.view');
        
        $application1 = LeaveApplication::factory()->create(['employee_id' => $employee1->id]);
        $application2 = LeaveApplication::factory()->create(['employee_id' => $employee2->id]);
        
        $response = $this->actingAs($user1)
            ->get(route('leave-applications.index'));
            
        $response->assertStatus(200);
        // Should see own application but not others
        $response->assertSee($application1->reason);
        $response->assertDontSee($application2->reason);
    }

    public function test_supervisor_can_approve_leave_applications()
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');
        $supervisor->givePermissionTo('leave.approve');
        
        $application = LeaveApplication::factory()->create(['status' => 'pending']);
        
        $response = $this->actingAs($supervisor)
            ->patch(route('leave-applications.update', $application), [
                'status' => 'approved',
                'supervisor_comments' => 'Approved for vacation'
            ]);
            
        $response->assertRedirect();
        $this->assertDatabaseHas('leave_applications', [
            'id' => $application->id,
            'status' => 'approved',
        ]);
    }

    public function test_supervisor_can_reject_leave_applications()
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');
        $supervisor->givePermissionTo('leave.approve');
        
        $application = LeaveApplication::factory()->create(['status' => 'pending']);
        
        $response = $this->actingAs($supervisor)
            ->patch(route('leave-applications.update', $application), [
                'status' => 'rejected',
                'supervisor_comments' => 'Insufficient leave balance'
            ]);
            
        $response->assertRedirect();
        $this->assertDatabaseHas('leave_applications', [
            'id' => $application->id,
            'status' => 'rejected',
        ]);
    }

    public function test_leave_application_status_updates_correctly()
    {
        $application = LeaveApplication::factory()->create(['status' => 'pending']);
        
        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');
        $supervisor->givePermissionTo('leave.approve');
        
        // Test status transition from pending to approved
        $response = $this->actingAs($supervisor)
            ->patch(route('leave-applications.update', $application), [
                'status' => 'approved'
            ]);
            
        $application->refresh();
        $this->assertEquals('approved', $application->status);
        
        // Test that approved application can be cancelled
        $response = $this->actingAs($supervisor)
            ->patch(route('leave-applications.update', $application), [
                'status' => 'cancelled'
            ]);
            
        $application->refresh();
        $this->assertEquals('cancelled', $application->status);
    }

    public function test_leave_application_displays_correct_information()
    {
        $employee = Employee::factory()->create();
        $user = $employee->user;
        $user->assignRole('Employee');
        $user->givePermissionTo('leave.view');
        
        $application = LeaveApplication::factory()->create([
            'employee_id' => $employee->id,
            'reason' => 'Medical appointment'
        ]);
        
        $response = $this->actingAs($user)
            ->get(route('leave-applications.show', $application));
            
        $response->assertStatus(200);
        $response->assertSee('Medical appointment');
        $response->assertSee($application->start_date);
        $response->assertSee($application->end_date);
    }
}