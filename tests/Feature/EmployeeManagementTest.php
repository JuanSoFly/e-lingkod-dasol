<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles and permissions
        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Employee']);
        
        Permission::create(['name' => 'employee.view']);
        Permission::create(['name' => 'employee.create']);
        Permission::create(['name' => 'employee.edit']);
        Permission::create(['name' => 'employee.delete']);
    }

    public function test_authenticated_admin_can_view_employees_index()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $admin->givePermissionTo('employee.view');
        
        Employee::factory()->count(5)->create();
        
        $response = $this->actingAs($admin)
            ->get(route('employees.index'));
            
        $response->assertStatus(200);
        $response->assertViewIs('employees.index');
        $response->assertViewHas('employees');
    }

    public function test_unauthorized_user_cannot_view_employees()
    {
        $user = User::factory()->create();
        $user->assignRole('Employee');
        
        $response = $this->actingAs($user)
            ->get(route('employees.index'));
            
        $response->assertStatus(403);
    }

    public function test_admin_can_create_employee()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $admin->givePermissionTo('employee.create');
        
        $employeeData = [
            'employee_number' => 'EMP001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@company.com',
            'phone' => '09171234567',
            'address' => '123 Main St',
            'birth_date' => '1990-01-01',
            'gender' => 'male',
            'civil_status' => 'single',
            'position' => 'Software Developer',
            'department' => 'IT',
            'date_hired' => '2024-01-01',
            'employment_status' => 'regular',
            'basic_salary' => '50000.00',
        ];
        
        $response = $this->actingAs($admin)
            ->post(route('employees.store'), $employeeData);
            
        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHas('success', 'Employee created successfully.');
        
        $this->assertDatabaseHas('employees', [
            'employee_number' => 'EMP001',
            'email' => 'john.doe@company.com',
        ]);
        
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john.doe@company.com',
        ]);
    }

    public function test_employee_creation_creates_associated_user_account()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $admin->givePermissionTo('employee.create');
        
        $employeeData = Employee::factory()->make()->toArray();
        
        $response = $this->actingAs($admin)
            ->post(route('employees.store'), $employeeData);
            
        $employee = Employee::where('email', $employeeData['email'])->first();
        $this->assertNotNull($employee);
        $this->assertNotNull($employee->user);
        $this->assertTrue($employee->user->hasRole('Employee'));
    }

    public function test_employee_creation_with_invalid_data_fails()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $admin->givePermissionTo('employee.create');
        
        $invalidData = [
            'first_name' => '', // Required field
            'email' => 'invalid-email', // Invalid email
        ];
        
        $response = $this->actingAs($admin)
            ->post(route('employees.store'), $invalidData);
            
        $response->assertSessionHasErrors(['first_name', 'email']);
    }

    public function test_admin_can_update_employee()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $admin->givePermissionTo('employee.edit');
        
        $employee = Employee::factory()->create();
        
        $updateData = [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => $employee->email,
            'phone' => $employee->phone,
            'address' => $employee->address,
            'birth_date' => $employee->birth_date,
            'gender' => $employee->gender,
            'civil_status' => $employee->civil_status,
            'position' => 'Senior Developer',
            'department' => $employee->department,
            'date_hired' => $employee->date_hired,
            'employment_status' => $employee->employment_status,
            'basic_salary' => $employee->basic_salary,
        ];
        
        $response = $this->actingAs($admin)
            ->put(route('employees.update', $employee), $updateData);
            
        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'first_name' => 'Updated',
            'position' => 'Senior Developer',
        ]);
    }

    public function test_admin_can_delete_employee()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $admin->givePermissionTo('employee.delete');
        
        $employee = Employee::factory()->create();
        $userId = $employee->user->id ?? null;
        
        $response = $this->actingAs($admin)
            ->delete(route('employees.destroy', $employee));
            
        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
        
        if ($userId) {
            $this->assertDatabaseMissing('users', ['id' => $userId]);
        }
    }

    public function test_employee_show_page_displays_correct_data()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $admin->givePermissionTo('employee.view');
        
        $employee = Employee::factory()->create();
        
        $response = $this->actingAs($admin)
            ->get(route('employees.show', $employee));
            
        $response->assertStatus(200);
        $response->assertViewIs('employees.show');
        $response->assertSee($employee->first_name);
        $response->assertSee($employee->email);
    }
}