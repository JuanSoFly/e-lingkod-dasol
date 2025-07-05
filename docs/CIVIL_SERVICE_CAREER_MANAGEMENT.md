# Civil Service Career Management System

## Overview

This documentation describes the comprehensive database migration and models created for Civil Service Career Management in the E-Lingkod Dasol HRIS system. The implementation follows Philippine government standards and Civil Service Commission (CSC) requirements.

## Database Tables Created

### 1. `civil_service_eligibilities` Table

**Purpose**: Track employee civil service examination results and professional eligibilities.

**Key Features**:
- Comprehensive tracking of CSC eligibility types
- Examination details with ratings and certificate numbers
- Validity periods and expiration tracking
- Document attachment support
- Verification workflow system

**Fields**:
- `id` - Primary key
- `employee_id` - Foreign key to employees table
- `eligibility_type` - ENUM (Professional, Sub-professional, Second Level, etc.)
- `examination_name` - Name of the examination taken
- `date_taken` - Date when examination was taken
- `rating` - Examination score/rating (decimal 5,2)
- `place_of_examination` - Where examination was conducted
- `certificate_number` - Unique certificate identifier
- `license_number` - Professional license number (if applicable)
- `valid_until` - Expiration date of eligibility
- `status` - ENUM (Active, Expired, Suspended, Revoked, Pending Verification)
- `remarks` - Additional notes
- `issuing_authority` - Organization that issued the eligibility
- `date_issued` - When the certificate was issued
- `is_lifetime_valid` - Boolean for lifetime eligibilities
- `is_verified` - Verification status
- `verified_at` - Timestamp of verification
- `verified_by` - User who verified the eligibility
- `certificate_file_path` - Path to uploaded certificate
- `verification_document_path` - Path to verification documents

**Indexes**:
- `employee_id, eligibility_type` (composite)
- `status, valid_until` (composite)
- `examination_name`
- `date_taken`

### 2. `career_progression` Table

**Purpose**: Track employee promotions, transfers, and career movements.

**Key Features**:
- Complete promotion history tracking
- Salary grade progression monitoring
- Official appointment documentation
- CSC approval tracking
- Temporary assignment support

**Fields**:
- `id` - Primary key
- `employee_id` - Foreign key to employees table
- `from_position` - Previous position title
- `to_position` - New position title
- `from_department` - Previous department
- `to_department` - New department
- `promotion_date` - Date of promotion/transfer
- `promotion_type` - ENUM (Regular Promotion, Merit Promotion, Lateral Transfer, etc.)
- `salary_grade_from` - Previous salary grade
- `salary_grade_to` - New salary grade
- `step_increment_from` - Previous step increment
- `step_increment_to` - New step increment
- `monthly_salary_from` - Previous monthly salary
- `monthly_salary_to` - New monthly salary
- `appointing_authority` - Who made the appointment
- `order_number` - Official order number
- `order_series` - Year/series of the order
- `order_date` - Date of the order
- `effective_date` - When the promotion takes effect
- `status` - ENUM (Active, Completed, Cancelled, Pending, Superseded)
- `from_employment_status` - Previous employment status
- `to_employment_status` - New employment status
- `nature_of_appointment` - ENUM (Original, Promotion, Transfer, etc.)
- `justification` - Reason for promotion/transfer
- `remarks` - Additional notes
- `is_temporary` - Boolean for temporary assignments
- `temporary_until` - End date for temporary assignments
- `appointment_document_path` - Path to appointment document
- `is_csc_approved` - CSC approval status
- `csc_approval_date` - Date of CSC approval
- `csc_approval_number` - CSC approval reference number
- `created_by` - User who created the record
- `approved_by` - User who approved the record
- `approved_at` - Timestamp of approval

**Indexes**:
- `employee_id, promotion_date` (composite)
- `promotion_type, status` (composite)
- `effective_date, status` (composite)
- `salary_grade_to, step_increment_to` (composite)
- `appointing_authority`
- `from_position, to_position` (composite)

### 3. `salary_grades` Table

**Purpose**: Store Philippine government salary standardization data.

**Key Features**:
- Complete SSL (Salary Standardization Law) implementation
- Automatic rate calculations
- Historical salary tracking
- Position level classifications
- Overtime and differential rates

**Fields**:
- `id` - Primary key
- `grade_level` - Salary Grade 1-33
- `step_increment` - Step 1-8
- `monthly_salary` - Base monthly salary
- `daily_rate` - Calculated daily rate (monthly/22)
- `hourly_rate` - Calculated hourly rate (daily/8)
- `pera_allowance` - Performance-based allowance
- `productivity_allowance` - Productivity incentive
- `hazard_allowance` - Hazard pay
- `subsistence_allowance` - Subsistence allowance
- `laundry_allowance` - Laundry allowance
- `overtime_rate_regular` - 125% of hourly rate
- `overtime_rate_special` - 130% of hourly rate (special holiday)
- `overtime_rate_legal` - 200% of hourly rate (legal holiday)
- `night_differential_rate` - 10% of hourly rate
- `position_level` - ENUM (First Level, Second Level, CES, etc.)
- `ssl_tranche` - ENUM (Tranche 1, 2, 3, 4)
- `effective_date` - When this salary grade becomes effective
- `end_date` - When this salary grade is superseded
- `status` - ENUM (Active, Superseded, Future, Cancelled)
- `dbu_number` - DBM Budget Circular/DBU reference
- `legal_basis` - Legal reference (RA/EO)
- `remarks` - Additional notes
- `annual_adjustment_percentage` - Percentage increase for the year
- `adjustment_year` - Year of adjustment

**Unique Constraints**:
- `grade_level, step_increment, effective_date` (unique)

**Indexes**:
- `grade_level, step_increment, status` (composite)
- `effective_date, status` (composite)
- `position_level, grade_level` (composite)
- `monthly_salary`

## Eloquent Models

### CivilServiceEligibility Model

**Key Methods**:
- `isValid()` - Check if eligibility is currently valid
- `isExpired()` - Check if eligibility has expired
- `daysUntilExpiration()` - Get days until expiration
- `isExpiringSoon()` - Check if expiring within specified days

**Relationships**:
- `employee()` - Belongs to Employee
- `verifier()` - Belongs to User (who verified)

**Scopes**:
- `active()` - Only active eligibilities
- `expired()` - Only expired eligibilities
- `verified()` - Only verified eligibilities

### CareerProgression Model

**Key Methods**:
- `isPromotion()` - Check if record represents a promotion
- `isLateralTransfer()` - Check if record is a lateral transfer
- `getSalaryIncrease()` - Calculate salary increase amount
- `getSalaryIncreasePercentage()` - Calculate percentage increase
- `isEffective()` - Check if effective date has passed

**Relationships**:
- `employee()` - Belongs to Employee
- `creator()` - Belongs to User (who created)
- `approver()` - Belongs to User (who approved)
- `fromSalaryGrade()` - Belongs to SalaryGrade
- `toSalaryGrade()` - Belongs to SalaryGrade

**Scopes**:
- `active()` - Only active progressions
- `promotions()` - Only actual promotions
- `lateralTransfers()` - Only lateral transfers
- `cscApproved()` - Only CSC approved records

### SalaryGrade Model

**Key Methods**:
- `getTotalMonthlyCompensation()` - Calculate total compensation
- `calculateOvertimePay()` - Calculate overtime for given hours
- `calculateNightDifferentialPay()` - Calculate night differential
- `isEffective()` - Check if currently effective
- `getNextStep()` - Get next step in same grade
- `getNextGrade()` - Get next grade level

**Relationships**:
- `employees()` - Has many Employees
- `careerProgressionsFrom()` - Has many CareerProgression (from)
- `careerProgressionsTo()` - Has many CareerProgression (to)

**Scopes**:
- `active()` - Only active salary grades
- `current()` - Only currently effective
- `forGrade()` - Filter by grade level
- `forStep()` - Filter by step increment

## Updated Employee Model

**New Relationships Added**:
- `civilServiceEligibilities()` - Has many CivilServiceEligibility
- `careerProgressions()` - Has many CareerProgression
- `currentSalaryGrade()` - Get current salary grade information

## Data Seeding

### SalaryGradeSeeder

**Features**:
- Complete 2024 Philippine Government salary data (SSL Tranche 4)
- All salary grades (SG 1-33) with 8 steps each
- Automatic calculation of rates and differentials
- Historical data (2023 SSL Tranche 3) for reference
- Future projections (2025 SSL Tranche 5) for planning

**Data Included**:
- 264 current salary grades (33 grades × 8 steps)
- 264 future salary projections
- 40 historical salary records
- Position level classifications
- Government allowances and benefits

## Usage Examples

### Check Employee Eligibilities
```php
$employee = Employee::find(1);
$activeEligibilities = $employee->civilServiceEligibilities()->active()->get();
$expiring = $employee->civilServiceEligibilities()
    ->where('valid_until', '<=', now()->addDays(30))
    ->get();
```

### Track Career Progression
```php
$promotions = $employee->careerProgressions()->promotions()->get();
$latestPromotion = $employee->careerProgressions()
    ->orderBy('effective_date', 'desc')
    ->first();
```

### Salary Grade Information
```php
$currentSalary = $employee->currentSalaryGrade();
$nextStep = $currentSalary?->getNextStep();
$totalCompensation = $currentSalary?->getTotalMonthlyCompensation();
```

## Migration Files

1. **2025_06_28_135519_create_civil_service_eligibilities_table.php**
2. **2025_06_28_135550_create_career_progression_table.php**
3. **2025_06_28_135628_create_salary_grades_table.php**

## Model Files

1. **app/Models/CivilServiceEligibility.php**
2. **app/Models/CareerProgression.php**
3. **app/Models/SalaryGrade.php**

## Seeder Files

1. **database/seeders/SalaryGradeSeeder.php**

## Government Compliance

This implementation ensures compliance with:
- Civil Service Commission (CSC) requirements
- Salary Standardization Law (SSL) provisions
- Philippine government career progression standards
- CSC eligibility and examination tracking
- Government benefit calculations

## Security Features

- Soft deletes for audit trails
- User tracking for record creation and approval
- Document attachment security
- Verification workflow system
- Role-based access control integration

---

*This system provides comprehensive career management functionality for Philippine government employees while maintaining full compliance with CSC standards and requirements.*