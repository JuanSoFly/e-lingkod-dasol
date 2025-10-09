<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NavigationSecurityTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Test employee navigation shows appropriate options
     */
    public function test_employee_navigation_is_role_appropriate()
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        $response = $this->actingAs($employee)->get('/dashboard');
        
        $response->assertStatus(200);
        
        // Should NOT see admin options
        $response->assertDontSee('All Requests');
        $response->assertDontSee('All Employees');
        $response->assertDontSee('Employees');
        $response->assertDontSee('HR Analytics');
        $response->assertDontSee('CSC Reports');
        
        // Should see employee options
        $response->assertSee('My Requests');
        $response->assertSee('Dashboard');
    }
    
    /**
     * Test HR Admin navigation shows all options
     */
    public function test_hr_admin_navigation_shows_all_options()
    {
        $hrAdmin = User::factory()->create();
        $hrAdmin->assignRole('HR Admin');
        
        $response = $this->actingAs($hrAdmin)->get('/dashboard');
        
        $response->assertStatus(200);
        
        // Should see admin options
        $response->assertSee('Employees');
        $response->assertSee('All Requests');
        
        // Should also see employee options
        $response->assertSee('My Requests');
    }
    
    /**
     * Test Super Admin navigation shows all system options
     */
    public function test_super_admin_navigation_shows_all_system_options()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');
        
        $response = $this->actingAs($superAdmin)->get('/dashboard');
        
        $response->assertStatus(200);
        
        // Should see all admin options
        $response->assertSee('Employees');
        $response->assertSee('All Requests');
        $response->assertSee('Dashboard');
    }
    
    /**
     * Test navigation permissions consistency
     */
    public function test_navigation_permissions_match_backend_authorization()
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        // If navigation doesn't show it, backend should block it
        $response = $this->actingAs($employee)->get('/employees');
        $response->assertStatus(403);
        
        $response = $this->actingAs($employee)->get('/leave_applications/admin');
        $response->assertStatus(403);
        
        // Test that HR Analytics is blocked for employees
        $response = $this->actingAs($employee)->get('/hr-analytics/dashboard');
        $response->assertStatus(403);
    }
    
    /**
     * Test sidebar navigation links are role-appropriate
     */
    public function test_sidebar_navigation_links_are_role_filtered()
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        $response = $this->actingAs($employee)->get('/dashboard');
        
        // Check that navigation HTML doesn't contain admin-only links
        $content = $response->getContent();
        
        // Should not contain admin navigation items
        $this->assertStringNotContainsString('href="/employees"', $content);
        $this->assertStringNotContainsString('href="/leave_applications/admin"', $content);
        $this->assertStringNotContainsString('href="/hr-analytics"', $content);
        $this->assertStringNotContainsString('href="/csc-reports"', $content);
        
        // Should contain employee navigation items
        $this->assertStringContainsString('href="/leave_applications"', $content);
        $this->assertStringContainsString('href="/dashboard"', $content);
    }
    
    /**
     * Test navigation adapts based on user permissions
     */
    public function test_navigation_adapts_to_user_permissions()
    {
        // Test with different roles
        $roles = ['Employee', 'HR Admin', 'Super Admin'];
        
        foreach ($roles as $roleName) {
            $user = User::factory()->create();
            $user->assignRole($roleName);
            
            $response = $this->actingAs($user)->get('/dashboard');
            $response->assertStatus(200);
            
            $content = $response->getContent();
            
            if ($roleName === 'Employee') {
                // Employees should only see limited navigation
                $this->assertStringNotContainsString('All Employees', $content);
                $this->assertStringContainsString('Leave Applications', $content);
            } else {
                // HR Admin and Super Admin should see administrative options
                $this->assertStringContainsString('Employees', $content);
            }
        }
    }
    
    /**
     * Test breadcrumb navigation security
     */
    public function test_breadcrumb_navigation_security()
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        // Test accessing employee's own profile
        $response = $this->actingAs($employee)->get("/employees/{$employee->employee->id}");
        $response->assertStatus(200);
        
        // Breadcrumb should not show admin paths
        $content = $response->getContent();
        $this->assertStringNotContainsString('All Employees', $content);
    }
    
    /**
     * Test mobile navigation security
     */
    public function test_mobile_navigation_is_secure()
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        // Test mobile menu doesn't expose admin options
        $response = $this->actingAs($employee)
            ->withHeaders(['User-Agent' => 'Mobile'])
            ->get('/dashboard');
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Mobile menu should also respect role restrictions
        $this->assertStringNotContainsString('All Employees', $content);
        $this->assertStringNotContainsString('HR Analytics', $content);
    }
    
    /**
     * Test navigation menu performance with large dataset
     */
    public function test_navigation_performance_with_large_dataset()
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        // Create large dataset to ensure navigation doesn't slow down
        \App\Models\Employee::factory()->count(1000)->create();
        \App\Models\LeaveApplication::factory()->count(5000)->create();
        
        $start = microtime(true);
        
        $response = $this->actingAs($employee)->get('/dashboard');
        
        $loadTime = microtime(true) - $start;
        
        $response->assertStatus(200);
        
        // Navigation should load quickly even with large dataset
        $this->assertLessThan(1.0, $loadTime, 'Navigation should load within 1 second');
    }
    
    /**
     * Test navigation consistency across different pages
     */
    public function test_navigation_consistency_across_pages()
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        $pages = [
            '/dashboard',
            '/leave_applications',
            "/employees/{$employee->employee->id}",
        ];
        
        foreach ($pages as $page) {
            $response = $this->actingAs($employee)->get($page);
            
            if ($response->status() === 200) {
                $content = $response->getContent();
                
                // Navigation should be consistent across all pages
                $this->assertStringContainsString('Leave Applications', $content);
                $this->assertStringNotContainsString('All Employees', $content);
            }
        }
    }
}