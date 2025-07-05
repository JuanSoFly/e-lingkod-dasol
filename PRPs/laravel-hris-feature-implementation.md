name: "Laravel HRIS Feature Implementation - Comprehensive PRP"
description: |

## Purpose
Template optimized for AI agents to implement new features in the E-Lingkod Dasol Laravel HRIS system with sufficient context and self-validation capabilities to achieve working code through iterative refinement.

## Core Principles
1. **Context is King**: Include ALL necessary documentation, examples, and caveats
2. **Validation Loops**: Provide executable tests/lints the AI can run and fix
3. **Information Dense**: Use keywords and patterns from the codebase
4. **Progressive Success**: Start simple, validate, then enhance
5. **Global rules**: Be sure to follow all rules in CLAUDE.md

---

## Goal
[What needs to be built - be specific about the end state and user-visible functionality]

## Why
- [Business value for the Municipality of Dasol and HR processes]
- [Integration with existing HRIS features and workflows]
- [Problems this solves for HR Admin, employees, or government compliance]

## What
[User-visible behavior and technical requirements]

### Success Criteria
- [ ] [Specific measurable outcomes aligned with Philippine government requirements]
- [ ] [CSC compliance if applicable]
- [ ] [Role-based access control implemented]
- [ ] [Integration with existing workflows]

## All Needed Context

### Documentation & References
```yaml
# MUST READ - Include these in your context window
- url: https://laravel.com/docs/12.x
  why: Laravel 12.x is the version used in this project
  
- url: https://github.com/spatie/laravel-permission
  why: RBAC system used throughout the application
  
- url: https://tailwindcss.com/docs
  why: Frontend CSS framework - utility-first approach
  
- url: https://alpinejs.dev/
  why: Frontend JavaScript framework for reactive components
  
- url: https://www.chartjs.org/docs/latest/
  why: Data visualization library used in dashboards
  
- file: app/Services/DashboardService.php
  why: Service layer pattern implementation with caching
  
- file: app/Http/Controllers/DashboardController.php
  why: Controller pattern with dependency injection and middleware
  
- file: app/Models/Employee.php
  why: Model relationships and soft deletes pattern
  
- file: app/Http/Requests/StoreEmployeeRequest.php
  why: FormRequest validation patterns
  
- file: resources/views/employees/index.blade.php
  why: Blade template structure with Tailwind CSS
  
- file: tests/Feature/EmployeeManagementTest.php
  why: Feature testing patterns with RefreshDatabase
  
- file: database/migrations/2025_06_26_154656_create_employees_table.php
  why: Migration patterns with indexes and foreign keys
  
- file: app/Providers/AppServiceProvider.php
  why: Service binding patterns
  
- file: routes/web.php
  why: Route organization with middleware and naming
  
- file: CLAUDE.md
  why: Project-specific guidelines and context
```

### Current Codebase Architecture
```bash
# E-Lingkod Dasol HRIS - Laravel 12.x
# 250+ employees, 43 migrations, 178 routes
# Technology: Laravel 12.x, MySQL 8.0+, Tailwind CSS, Alpine.js, Chart.js

app/
├── Http/Controllers/        # 18 main controllers
│   ├── DashboardController.php          # Role-based dashboard
│   ├── EmployeeController.php           # Employee lifecycle (201 files)
│   ├── LeaveApplicationController.php   # Leave management workflows
│   ├── CSCReportManagementController.php # Government compliance
│   └── HRAnalyticsController.php        # Analytics with API endpoints
├── Services/               # Service layer (business logic)
│   ├── DashboardService.php            # Cached metrics
│   ├── CSCReportingService.php         # Government reporting
│   ├── LeaveCalculationService.php     # Pro-rated calculations
│   └── DocumentSearchService.php       # OCR and full-text search
├── Models/                 # 25+ Eloquent models with relationships
│   ├── Employee.php        # Core employee model with soft deletes
│   ├── LeaveApplication.php # Leave management
│   └── User.php           # Authentication with roles
├── Http/Requests/          # Form validation
│   ├── StoreEmployeeRequest.php
│   └── UpdateEmployeeRequest.php
├── Policies/              # Authorization policies
│   ├── EmployeeDocumentPolicy.php
│   └── LeaveApplicationPolicy.php
├── Notifications/         # Laravel notifications
│   ├── LeaveApplicationSubmitted.php
│   └── NewEmployeeAccountCreated.php
├── Exports/              # Excel exports
│   ├── EmployeesExport.php
│   └── LeaveBalanceExport.php
├── Jobs/                 # Background jobs
│   └── IndexDocumentContentJob.php
└── Console/Commands/     # Custom artisan commands
    ├── ClearDashboardCache.php
    └── DocumentManagementCommand.php

database/
├── migrations/          # 43 migrations with performance optimization
├── seeders/            # Comprehensive seeders with sample data
└── factories/          # 15+ model factories for testing

resources/
├── views/              # Blade templates with Tailwind CSS
│   ├── employees/      # CRUD operations
│   ├── dashboard.blade.php # Role-based dashboard
│   ├── components/     # Reusable Blade components
│   └── layouts/        # App and guest layouts
├── css/app.css        # Tailwind CSS with custom styles
└── js/app.js          # Alpine.js and Chart.js integration

tests/
├── Feature/           # Integration tests
│   ├── EmployeeManagementTest.php
│   ├── LeaveApplicationTest.php
│   └── HRAnalyticsTest.php
└── Unit/              # Unit tests for services
    ├── CSCReportingServiceTest.php
    └── DocumentSearchServiceTest.php

routes/
├── web.php            # 178 routes with middleware
├── api.php            # API endpoints
└── auth.php           # Authentication routes (Laravel Breeze)
```

### Known Gotchas & Library Quirks
```php
// CRITICAL: Laravel 12.x specific changes
// AuthorizesRequests and ValidatesRequests traits removed from base Controller
// Use explicit traits if needed: use AuthorizesRequests, ValidatesRequests;

// CRITICAL: This project uses MySQL 8.0+ - all operations must be MySQL-specific
// Example: Use MySQL-specific features like JSON columns, spatial indexes

// CRITICAL: Spatie/Laravel-Permission requires specific patterns
// Always use ->assignRole() and ->givePermissionTo() methods
// Check permissions with ->can() or Gate::allows()

// CRITICAL: Tailwind CSS utility-first - avoid custom CSS
// Example: Use 'bg-blue-500 hover:bg-blue-600' not custom classes

// CRITICAL: Alpine.js patterns for interactivity
// Example: x-data="{ open: false }" for component state

// CRITICAL: Service layer uses interface contracts
// Always bind services to interfaces in AppServiceProvider
// Example: $this->app->bind(DashboardServiceInterface::class, DashboardService::class);

// CRITICAL: Philippine government compliance requirements
// All date formats must follow Philippine standards
// Employee numbers must follow government formats
// CSC reporting must use exact Philippine terminology

// CRITICAL: Redis caching patterns
// Use cache keys like 'dashboard:user:123:metrics'
// Cache duration: 5 minutes for dashboard data
// Clear cache on data updates

// CRITICAL: Soft deletes are used throughout
// Always use ->withTrashed() when needed
// Use ->restore() for recovery operations

// CRITICAL: File uploads go to storage/app/private/employee_documents/{id}/
// Use Storage::disk('private') for document handling
// Implement proper file security and access control
```

## Implementation Blueprint

### Data Models and Structure
```php
// Follow existing patterns from app/Models/Employee.php
class NewFeatureModel extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'field1', 'field2', 'user_id', 'employee_id'
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    // Relationships following existing patterns
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    // Scopes for common queries
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
}
```

### List of tasks to be completed in order
```yaml
Task 1: Create Database Migration
CREATE database/migrations/YYYY_MM_DD_HHMMSS_create_new_feature_table.php:
  - FOLLOW pattern from: database/migrations/2025_06_26_154656_create_employees_table.php
  - INCLUDE foreign key constraints and indexes
  - USE MySQL 8.0+ specific features if needed
  - ADD soft deletes: $table->softDeletes();
  - ADD timestamps: $table->timestamps();

Task 2: Create Eloquent Model
CREATE app/Models/NewFeatureModel.php:
  - MIRROR pattern from: app/Models/Employee.php
  - INCLUDE proper relationships (BelongsTo, HasMany)
  - USE SoftDeletes trait
  - DEFINE fillable fields and casts
  - ADD query scopes for common filters

Task 3: Create Service Interface
CREATE app/Contracts/NewFeatureServiceInterface.php:
  - FOLLOW pattern from: app/Contracts/DashboardServiceInterface.php
  - DEFINE all public methods
  - INCLUDE proper return types and parameter types

Task 4: Create Service Implementation
CREATE app/Services/NewFeatureService.php:
  - IMPLEMENT the interface from Task 3
  - FOLLOW pattern from: app/Services/DashboardService.php
  - INCLUDE error handling and logging
  - USE caching where appropriate (Redis)
  - IMPLEMENT business logic separation

Task 5: Create Form Request Classes
CREATE app/Http/Requests/StoreNewFeatureRequest.php:
CREATE app/Http/Requests/UpdateNewFeatureRequest.php:
  - FOLLOW pattern from: app/Http/Requests/StoreEmployeeRequest.php
  - INCLUDE proper validation rules
  - USE custom validation messages
  - IMPLEMENT authorization logic

Task 6: Create Controller
CREATE app/Http/Controllers/NewFeatureController.php:
  - FOLLOW pattern from: app/Http/Controllers/EmployeeController.php
  - INJECT service interface in constructor
  - USE middleware for authentication and authorization
  - IMPLEMENT standard resource methods (index, create, store, show, edit, update, destroy)
  - RETURN proper responses (view, redirect, JSON)

Task 7: Create Policy Class
CREATE app/Policies/NewFeaturePolicy.php:
  - FOLLOW pattern from: app/Policies/EmployeeDocumentPolicy.php
  - IMPLEMENT authorization methods (view, create, update, delete)
  - USE role-based permissions
  - REGISTER in AuthServiceProvider

Task 8: Create Routes
MODIFY routes/web.php:
  - FOLLOW pattern from existing routes
  - USE resource routes with middleware
  - IMPLEMENT proper naming conventions
  - ADD API routes if needed

Task 9: Create Blade Templates
CREATE resources/views/new-feature/:
  - FOLLOW pattern from: resources/views/employees/
  - CREATE index.blade.php (listing with search/filter)
  - CREATE create.blade.php (form for new records)
  - CREATE show.blade.php (detailed view)
  - CREATE edit.blade.php (edit form)
  - USE Tailwind CSS classes consistently
  - IMPLEMENT responsive design
  - ADD Alpine.js for interactivity

Task 10: Create Blade Components (if needed)
CREATE resources/views/components/new-feature-card.blade.php:
  - FOLLOW pattern from existing components
  - USE Tailwind CSS for styling
  - MAKE responsive and accessible

Task 11: Update Navigation
MODIFY resources/views/layouts/navigation.blade.php:
  - ADD new menu items with proper permissions
  - FOLLOW existing navigation patterns
  - USE role-based visibility

Task 12: Create Notification Classes (if needed)
CREATE app/Notifications/NewFeatureNotification.php:
  - FOLLOW pattern from: app/Notifications/LeaveApplicationSubmitted.php
  - IMPLEMENT proper channels (mail, database)
  - USE Laravel's notification system

Task 13: Create Factory and Seeder
CREATE database/factories/NewFeatureFactory.php:
CREATE database/seeders/NewFeatureSeeder.php:
  - FOLLOW pattern from existing factories
  - GENERATE realistic test data
  - UPDATE DatabaseSeeder.php

Task 14: Bind Service to Interface
MODIFY app/Providers/AppServiceProvider.php:
  - BIND service interface to implementation
  - FOLLOW pattern from existing bindings

Task 15: Create Feature Tests
CREATE tests/Feature/NewFeatureTest.php:
  - FOLLOW pattern from: tests/Feature/EmployeeManagementTest.php
  - USE RefreshDatabase trait
  - TEST all CRUD operations
  - TEST authentication and authorization
  - TEST validation rules

Task 16: Create Unit Tests
CREATE tests/Unit/NewFeatureServiceTest.php:
  - FOLLOW pattern from: tests/Unit/CSCReportingServiceTest.php
  - TEST service methods in isolation
  - MOCK dependencies where appropriate
  - TEST edge cases and error handling

Task 17: Update Documentation
MODIFY CLAUDE.md (if needed):
  - ADD any new patterns or conventions
  - UPDATE feature list if significant
```

### Integration Points
```yaml
DATABASE:
  - migration: "Add new_feature_table with proper indexes"
  - foreign_keys: "Link to users and employees tables"
  - soft_deletes: "Implement soft deletes for data integrity"

AUTHENTICATION:
  - middleware: "Use 'auth' middleware on all routes"
  - authorization: "Implement role-based access control"
  - policies: "Create policy for fine-grained permissions"

FRONTEND:
  - tailwind: "Use utility-first CSS approach"
  - alpine: "Add reactive components with Alpine.js"
  - charts: "Use Chart.js for data visualization if needed"

CACHING:
  - redis: "Implement caching for expensive operations"
  - cache_keys: "Use hierarchical cache key naming"
  - invalidation: "Clear cache on data updates"

VALIDATION:
  - form_requests: "Use FormRequest classes for validation"
  - rules: "Follow Philippine government data formats"
  - messages: "Provide user-friendly error messages"

TESTING:
  - feature_tests: "Test complete workflows"
  - unit_tests: "Test service methods in isolation"
  - factories: "Create realistic test data"
```

## Validation Loop

### Level 1: Syntax & Style
```bash
# Run these FIRST - fix any errors before proceeding
php artisan config:clear        # Clear config cache
php artisan route:clear         # Clear route cache
php artisan view:clear          # Clear view cache

# Check for syntax errors
php artisan serve --host=127.0.0.1 --port=8000

# Validate migration
php artisan migrate:status      # Check migration status
php artisan migrate --dry-run   # Test migration without applying

# Expected: No errors. If errors, READ and fix.
```

### Level 2: Database & Migration Tests
```bash
# Test database operations
php artisan migrate:fresh --seed  # Fresh migration with seeders

# Verify tables and relationships
php artisan tinker
# Test model relationships in tinker
# Example: App\Models\NewFeatureModel::with('employee')->first()
```

### Level 3: Unit Tests
```bash
# Run unit tests first
php artisan test --testsuite=Unit

# Run specific test class
php artisan test tests/Unit/NewFeatureServiceTest.php

# Expected: All tests pass. If failing, debug and fix.
```

### Level 4: Feature Tests
```bash
# Run feature tests
php artisan test --testsuite=Feature

# Run specific feature test
php artisan test tests/Feature/NewFeatureTest.php

# Test with coverage (optional)
php artisan test --coverage

# Expected: All tests pass with proper authentication/authorization
```

### Level 5: Integration Tests
```bash
# Start the development server
php artisan serve --host=127.0.0.1 --port=8000

# Test in browser with different user roles:
# 1. Login as hr@example.com (HR Admin)
# 2. Login as admin@example.com (Super Admin)  
# 3. Login as employee@example.com (Employee)

# Test complete workflows:
# - Create new record
# - Edit existing record
# - Delete record (soft delete)
# - View with proper permissions
# - Search and filter functionality

# Expected: All operations work correctly with proper access control
```

## Final Validation Checklist
- [ ] Migration runs without errors: `php artisan migrate`
- [ ] All relationships work: Test in `php artisan tinker`
- [ ] All tests pass: `php artisan test`
- [ ] Routes accessible with proper middleware: Check `/new-feature` routes
- [ ] CRUD operations work for all user roles
- [ ] Validation rules prevent invalid data entry
- [ ] Soft deletes work properly
- [ ] Caching implemented where appropriate
- [ ] Responsive design works on mobile/tablet
- [ ] No console errors in browser
- [ ] Performance is acceptable (page load < 3 seconds)
- [ ] Code follows Laravel 12.x patterns
- [ ] Documentation updated if needed

---

## Anti-Patterns to Avoid
- ❌ Don't bypass service layer - always use services for business logic
- ❌ Don't skip authorization - implement proper role-based access control
- ❌ Don't ignore soft deletes - use them for data integrity
- ❌ Don't hardcode values - use config files and environment variables
- ❌ Don't skip validation - always validate user input
- ❌ Don't ignore caching - implement Redis caching for expensive operations
- ❌ Don't use raw SQL - use Eloquent ORM and Query Builder
- ❌ Don't skip tests - write comprehensive feature and unit tests
- ❌ Don't ignore responsive design - ensure mobile compatibility
- ❌ Don't violate Philippine government compliance requirements

## Philippine Government Specific Requirements
- ✅ Use proper Philippine date formats (MM/DD/YYYY)
- ✅ Follow Civil Service Commission (CSC) terminology
- ✅ Implement proper audit trails for government compliance
- ✅ Use Philippine phone number formats (+63 format)
- ✅ Support Filipino and English languages
- ✅ Follow government security standards
- ✅ Implement proper document retention policies

## Confidence Score: 9/10

High confidence due to:
- ✅ Comprehensive existing codebase with clear patterns
- ✅ Well-documented Laravel 12.x framework
- ✅ Established service layer architecture
- ✅ Existing test patterns to follow
- ✅ Clear database migration patterns
- ✅ Established role-based access control
- ✅ Comprehensive validation patterns
- ✅ Professional UI/UX patterns with Tailwind CSS

Minor uncertainty:
- Philippine government compliance requirements may need verification
- Specific business logic may require clarification
- Integration with existing workflows may need adjustment

This PRP provides comprehensive context for implementing new features in the E-Lingkod Dasol HRIS system following established Laravel best practices and project-specific patterns.
