<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SecurityPerformanceTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Test role-based filtering performance
     */
    public function test_role_based_filtering_performance()
    {
        // Create large dataset
        Employee::factory()->count(1000)->create();
        
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        $hrAdmin = User::factory()->create();
        $hrAdmin->assignRole('HR Admin');
        
        // Test employee access (should be fast even with large dataset)
        $start = microtime(true);
        
        $this->actingAs($employee)->get('/leave_applications');
        
        $employeeTime = microtime(true) - $start;
        
        // Test HR Admin access
        $start = microtime(true);
        
        $this->actingAs($hrAdmin)->get('/employees');
        
        $hrAdminTime = microtime(true) - $start;
        
        // Both should complete within reasonable time (2 seconds)
        $this->assertLessThan(2.0, $employeeTime, 'Employee access should be fast');
        $this->assertLessThan(2.0, $hrAdminTime, 'HR Admin access should be fast');
        
        // Employee queries should be faster (more filtered)
        $this->assertLessThan($hrAdminTime, $employeeTime, 'Employee queries should be more efficient');
    }
    
    /**
     * Test audit logging performance impact
     */
    public function test_audit_logging_performance_impact()
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        // Test without audit logging
        config(['activitylog.enabled' => false]);
        
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $this->actingAs($employee)->get("/employees/{$employee->employee->id}");
        }
        $timeWithoutLogging = microtime(true) - $start;
        
        // Test with audit logging
        config(['activitylog.enabled' => true]);
        
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $this->actingAs($employee)->get("/employees/{$employee->employee->id}");
        }
        $timeWithLogging = microtime(true) - $start;
        
        // Logging should not significantly impact performance (less than 50% overhead)
        $overhead = ($timeWithLogging - $timeWithoutLogging) / $timeWithoutLogging;
        $this->assertLessThan(0.5, $overhead, 'Audit logging overhead should be reasonable');
    }
    
    /**
     * Test database query performance with security filters
     */
    public function test_database_query_performance_with_security_filters()
    {
        // Create large dataset
        Employee::factory()->count(1000)->create();
        
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        // Test query performance with security scopes
        DB::enableQueryLog();
        
        $start = microtime(true);
        
        // Simulate filtered query (employee can only see own data)
        $requests = \App\Models\LeaveApplication::where('employee_id', $employee->employee->id)->get();
        
        $queryTime = microtime(true) - $start;
        $queries = DB::getQueryLog();
        
        // Query should be fast and efficient
        $this->assertLessThan(0.1, $queryTime, 'Filtered queries should be fast');
        $this->assertLessThan(5, count($queries), 'Should use minimal queries');
        
        // Check that proper indexes are being used
        $mainQuery = $queries[0]['query'] ?? '';
        $this->assertStringContainsString('employee_id', $mainQuery, 'Query should filter by employee_id');
        
        DB::disableQueryLog();
    }
    
    /**
     * Test permission checking performance
     */
    public function test_permission_checking_performance()
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        // Test repeated permission checks
        $start = microtime(true);
        
        for ($i = 0; $i < 1000; $i++) {
            $employee->hasPermissionTo('employee.view-own');
            $employee->hasRole('Employee');
        }
        
        $permissionCheckTime = microtime(true) - $start;
        
        // Permission checks should be fast (cached)
        $this->assertLessThan(0.5, $permissionCheckTime, 'Permission checks should be fast');
    }
    
    /**
     * Test session security performance
     */
    public function test_session_security_performance()
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        // Test session validation performance
        $start = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $response = $this->actingAs($employee)->get("/employees/{$employee->employee->id}");
            $response->assertStatus(200);
        }
        
        $sessionValidationTime = microtime(true) - $start;
        
        // Session validation should not significantly impact performance
        $this->assertLessThan(5.0, $sessionValidationTime, 'Session validation should be efficient');
    }
    
    /**
     * Test caching effectiveness for security features
     */
    public function test_caching_effectiveness_for_security_features()
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        Cache::flush(); // Start with clean cache
        
        // First access (should populate cache)
        $start = microtime(true);
        $this->actingAs($employee)->get("/employees/{$employee->employee->id}");
        $firstAccessTime = microtime(true) - $start;
        
        // Second access (should use cache)
        $start = microtime(true);
        $this->actingAs($employee)->get("/employees/{$employee->employee->id}");
        $cachedAccessTime = microtime(true) - $start;
        
        // Cached access should be faster
        $this->assertLessThan($firstAccessTime, $cachedAccessTime, 'Cached access should be faster');
    }
    
    /**
     * Test API response time with security filtering
     */
    public function test_api_response_time_with_security_filtering()
    {
        // Create large dataset
        Employee::factory()->count(500)->create();
        
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        // Create some leave applications for the employee
        \App\Models\LeaveApplication::factory()->count(50)->create([
            'employee_id' => $employee->employee->id
        ]);
        
        // Test API performance
        $start = microtime(true);
        
        $response = $this->actingAs($employee)->getJson('/api/leave-applications');
        
        $apiResponseTime = microtime(true) - $start;
        
        $response->assertStatus(200);
        
        // API should respond quickly even with large dataset
        $this->assertLessThan(1.0, $apiResponseTime, 'API should respond within 1 second');
        
        $data = $response->json();
        
        // Should only return employee's own data
        foreach ($data['data'] as $request) {
            $this->assertEquals($employee->employee->id, $request['employee_id']);
        }
    }
    
    /**
     * Test concurrent user performance
     */
    public function test_concurrent_user_performance()
    {
        // Create multiple users
        $users = [];
        for ($i = 0; $i < 10; $i++) {
            $user = User::factory()->create();
            $user->assignRole('Employee');
            $users[] = $user;
        }
        
        // Simulate concurrent access
        $start = microtime(true);
        
        foreach ($users as $user) {
            $response = $this->actingAs($user)->get("/employees/{$user->employee->id}");
            $response->assertStatus(200);
        }
        
        $concurrentAccessTime = microtime(true) - $start;
        
        // Concurrent access should be handled efficiently
        $this->assertLessThan(5.0, $concurrentAccessTime, 'Concurrent access should be efficient');
    }
    
    /**
     * Test memory usage with security features
     */
    public function test_memory_usage_with_security_features()
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        $memoryBefore = memory_get_usage();
        
        // Perform security-intensive operations
        for ($i = 0; $i < 50; $i++) {
            $this->actingAs($employee)->get("/employees/{$employee->employee->id}");
        }
        
        $memoryAfter = memory_get_usage();
        $memoryIncrease = $memoryAfter - $memoryBefore;
        
        // Memory usage should not grow excessively
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease, 'Memory usage should be reasonable'); // 50MB limit
    }
    
    /**
     * Test database connection efficiency with security
     */
    public function test_database_connection_efficiency_with_security()
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        // Monitor database connections
        DB::enableQueryLog();
        
        $response = $this->actingAs($employee)->get("/employees/{$employee->employee->id}");
        $response->assertStatus(200);
        
        $queries = DB::getQueryLog();
        
        // Should use efficient number of queries
        $this->assertLessThan(10, count($queries), 'Should use minimal database queries');
        
        // Check for N+1 query problems
        $duplicateQueries = array_count_values(array_column($queries, 'query'));
        foreach ($duplicateQueries as $query => $count) {
            $this->assertLessThan(5, $count, "Query should not be repeated excessively: {$query}");
        }
        
        DB::disableQueryLog();
    }
    
    /**
     * Test security validation performance under load
     */
    public function test_security_validation_performance_under_load()
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        
        // Create some data for the employee
        \App\Models\LeaveApplication::factory()->count(100)->create([
            'employee_id' => $employee->employee->id
        ]);
        
        // Test multiple rapid requests
        $times = [];
        
        for ($i = 0; $i < 20; $i++) {
            $start = microtime(true);
            
            $response = $this->actingAs($employee)->get('/leave_applications');
            $response->assertStatus(200);
            
            $times[] = microtime(true) - $start;
        }
        
        $averageTime = array_sum($times) / count($times);
        $maxTime = max($times);
        
        // Performance should be consistent under load
        $this->assertLessThan(1.0, $averageTime, 'Average response time should be under 1 second');
        $this->assertLessThan(2.0, $maxTime, 'Maximum response time should be under 2 seconds');
        
        // Check for performance degradation
        $firstHalf = array_slice($times, 0, 10);
        $secondHalf = array_slice($times, 10, 10);
        
        $firstHalfAvg = array_sum($firstHalf) / count($firstHalf);
        $secondHalfAvg = array_sum($secondHalf) / count($secondHalf);
        
        // Performance should not degrade significantly under load
        $degradation = ($secondHalfAvg - $firstHalfAvg) / $firstHalfAvg;
        $this->assertLessThan(0.5, $degradation, 'Performance should not degrade more than 50% under load');
    }
}