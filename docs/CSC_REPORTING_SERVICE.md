# CSC Reporting Service Documentation

## Overview

The CSC (Civil Service Commission) Reporting Service is a comprehensive solution for generating all required Philippine Civil Service Commission reports for government HRIS systems. This service handles the five main types of CSC reports that government agencies must submit regularly.

## Supported Reports

### 1. Monthly Accession Report
Tracks new hires, appointments, and transfers-in during a specific month.

**Key Features:**
- New appointments by employment status
- Promotion tracking
- Transfer-in monitoring
- Detailed employee information
- Summary statistics

### 2. Monthly Separation Report
Tracks resignations, retirements, terminations, and transfers-out during a specific month.

**Key Features:**
- Separation by type (resignation, retirement, termination, etc.)
- Length of service calculation
- Separation reason tracking
- Department impact analysis

### 3. Monthly DIBAR Report (Dropped from the Rolls)
Tracks employees who are AWOL (Absent Without Official Leave) for 30+ days and are candidates for being dropped from the rolls.

**Key Features:**
- AWOL threshold monitoring (30+ days)
- Attendance pattern analysis
- Recommendations for administrative action
- Department-wise AWOL tracking

### 4. Monthly Sexual Harassment Cases Report
Tracks sexual harassment cases filed, investigated, and resolved during a specific month.

**Key Features:**
- Case status tracking (filed, under investigation, resolved, dismissed)
- Investigation timeline monitoring
- Resolution type analysis
- Confidentiality protection
- Court forwarding tracking
- Appeal monitoring

### 5. Annual IGHR Report (Inventory of Government Human Resources)
Comprehensive annual inventory of all government human resources with demographic and performance analysis.

**Key Features:**
- Workforce demographics analysis
- Employment status distribution
- Salary grade distribution
- Performance metrics
- Turnover rate calculation
- Training and development statistics
- Strategic recommendations

## Service Architecture

### Class Structure

```php
CSCReportingService implements CSCReportingServiceInterface
├── Monthly Reports
│   ├── generateMonthlyAccessionReport()
│   ├── generateMonthlySeparationReport()
│   ├── generateMonthlyDIBARReport()
│   └── generateMonthlySexualHarassmentReport()
├── Annual Reports
│   └── generateAnnualIGHRReport()
├── Export Functions
│   ├── exportToPDF()
│   └── exportToExcel()
├── Validation & Caching
│   ├── validateReportParameters()
│   └── clearReportCache()
└── Helper Methods
    ├── formatHarassmentCases()
    ├── calculateAverageAge()
    ├── calculateLengthOfService()
    └── generateRecommendations()
```

### Database Enhancements

The service includes database migrations to add CSC-required fields:

#### Employee Table Additions:
- **Appointment Information**: `appointment_type`, `appointment_date`
- **Separation Information**: `separation_type`, `separation_date`, `separation_reason`
- **Personal Information**: `place_of_birth`, `citizenship`, `religion`, `height`, `weight`, `blood_type`
- **Government IDs**: `tin_number`, `sss_number`, `pagibig_number`, `philhealth_number`, `gsis_number`
- **Performance Tracking**: `latest_performance_rating`, `latest_performance_date`
- **AWOL Monitoring**: `last_attendance_date`, `consecutive_absent_days`, `is_awol`, `awol_start_date`
- **Emergency Contacts**: `emergency_contact_name`, `emergency_contact_relationship`, etc.

#### New Sexual Harassment Cases Table:
- Complete case management system
- Investigation tracking
- Resolution monitoring
- Court proceedings tracking
- Appeal management

## Usage Examples

### Basic Report Generation

```php
// Inject the service
$cscService = app(CSCReportingService::class);

// Generate Monthly Accession Report
$accessionReport = $cscService->generateMonthlyAccessionReport(2025, 6);

// Generate Annual IGHR Report
$ighrReport = $cscService->generateAnnualIGHRReport(2024);

// Generate report for specific department
$deptReport = $cscService->generateMonthlySeparationReport(2025, 6, 'Human Resources');
```

### Report Export

```php
// Export to PDF
$reportData = $cscService->generateMonthlyAccessionReport(2025, 6);
return $cscService->exportToPDF($reportData, 'Monthly Accession Report');

// Export to Excel
return $cscService->exportToExcel($reportData, 'Monthly Accession Report');
```

### Parameter Validation

```php
$params = [
    'year' => 2025,
    'month' => 6,
    'department' => 'Finance'
];

$errors = $cscService->validateReportParameters($params);
if (empty($errors)) {
    // Parameters are valid, generate report
    $report = $cscService->generateMonthlyAccessionReport($params['year'], $params['month'], $params['department']);
}
```

### Cache Management

```php
// Clear all report caches
$cscService->clearReportCache();

// Clear specific report type cache
$cscService->clearReportCache('accession');
```

## API Endpoints

The `CSCReportController` provides RESTful endpoints for all report operations:

### Monthly Reports
- `POST /api/csc-reports/monthly/accession` - Generate Monthly Accession Report
- `POST /api/csc-reports/monthly/separation` - Generate Monthly Separation Report
- `POST /api/csc-reports/monthly/dibar` - Generate Monthly DIBAR Report
- `POST /api/csc-reports/monthly/harassment` - Generate Sexual Harassment Report

### Annual Reports
- `POST /api/csc-reports/annual/ighr` - Generate Annual IGHR Report

### Export Functions
- `POST /api/csc-reports/export/pdf` - Export report to PDF
- `POST /api/csc-reports/export/excel` - Export report to Excel

### Utility Endpoints
- `GET /api/csc-reports/departments` - Get available departments
- `POST /api/csc-reports/validate` - Validate report parameters
- `DELETE /api/csc-reports/cache` - Clear report cache

## Request/Response Examples

### Request Format
```json
{
    "year": 2025,
    "month": 6,
    "department": "Human Resources"
}
```

### Response Format
```json
{
    "success": true,
    "data": {
        "report_type": "Monthly Accession Report",
        "period": "June 2025",
        "department": "Human Resources",
        "generated_at": "2025-06-28 10:30:00",
        "total_accessions": 5,
        "data": [
            {
                "employment_status": "Permanent",
                "count": 3,
                "employees": [...]
            }
        ],
        "summary": {
            "total_by_status": {
                "Permanent": 3,
                "Temporary": 2
            },
            "highest_grade": 18,
            "departments_affected": 1
        }
    }
}
```

## Performance Features

### Caching Strategy
- **Cache Duration**: 15 minutes for report data
- **Cache Keys**: Unique keys per report type, period, and department
- **Cache Invalidation**: Manual and automatic cache clearing

### Database Optimization
- **Indexes**: Strategic indexes on frequently queried fields
- **MySQL Compatibility**: All queries optimized for MySQL
- **Soft Deletes**: Proper handling of deleted records for separation reports

### Memory Management
- **Large Dataset Handling**: Chunked processing for large employee lists
- **Progressive Loading**: Lazy loading of related data
- **Export Optimization**: Streaming for large file exports

## Security Features

### Access Control
- **Authentication Required**: All endpoints require authenticated users
- **Permission-Based Access**: `report.view` and `report.export` permissions
- **Role-Based Filtering**: Department heads see only their department data

### Data Protection
- **Confidentiality**: Sexual harassment cases marked as confidential
- **Sensitive Data Masking**: Personal information protection in exports
- **Audit Logging**: All report generation activities logged

### Input Validation
- **Parameter Validation**: Comprehensive validation for all inputs
- **SQL Injection Prevention**: Parameterized queries throughout
- **Cross-Site Scripting (XSS) Protection**: Output sanitization

## Configuration Requirements

### Environment Setup
- **PHP Version**: 8.2+
- **Laravel Version**: 12.x
- **Database**: MySQL (required for CSC compatibility)
- **Extensions**: PHP Excel, PDF generation libraries

### Package Dependencies
- `maatwebsite/excel` - Excel export functionality
- `barryvdh/laravel-dompdf` - PDF generation
- `spatie/laravel-permission` - Role and permission management
- `laravel/sanctum` - API authentication

### Database Configuration
```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'e_lingkod_dasol'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
]
```

## Migration Instructions

### 1. Run Database Migrations
```bash
php artisan migrate
```

### 2. Update Employee Records
```bash
# Update existing employee records with CSC fields
php artisan db:seed --class=CSCFieldsSeeder
```

### 3. Configure Permissions
```bash
# Create CSC reporting permissions
php artisan permission:create-csc-permissions
```

### 4. Clear Caches
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Compliance Notes

### CSC Requirements
- **Data Accuracy**: All employee data must be accurate and up-to-date
- **Timeliness**: Reports must be submitted within prescribed deadlines
- **Completeness**: All required fields must be populated
- **Format Standards**: Reports must follow CSC-prescribed formats

### Legal Compliance
- **Data Privacy Act**: Personal information protection measures
- **Sexual Harassment Reporting**: Proper case documentation and confidentiality
- **Government Audit Requirements**: Complete audit trails and documentation

## Troubleshooting

### Common Issues

1. **Cache Not Clearing**
   - Solution: Use Redis instead of file cache for better performance
   - Command: `php artisan config:cache`

2. **Memory Limits on Large Exports**
   - Solution: Increase PHP memory limit or implement chunked exports
   - Config: `ini_set('memory_limit', '512M')`

3. **PDF Generation Failures**
   - Solution: Check dompdf configuration and font availability
   - Command: `php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"`

4. **Excel Export Errors**
   - Solution: Verify maatwebsite/excel configuration
   - Command: `php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider"`

### Performance Optimization

1. **Database Queries**
   - Use eager loading for relationships
   - Implement database indexes for report queries
   - Consider database query caching

2. **Report Generation**
   - Implement background job processing for large reports
   - Use pagination for web-based report viewing
   - Consider report scheduling for recurring reports

3. **File Exports**
   - Store exported files temporarily for repeated downloads
   - Implement file compression for large exports
   - Use CDN for file delivery if needed

## Future Enhancements

### Planned Features
- **Report Scheduling**: Automated report generation and delivery
- **Dashboard Integration**: Real-time CSC compliance metrics
- **API Rate Limiting**: Protection against excessive API calls
- **Multi-language Support**: Tagalog and English report options
- **Advanced Analytics**: Predictive analytics for HR planning

### Integration Possibilities
- **CSC Portal Integration**: Direct submission to CSC systems
- **Email Notifications**: Automated report delivery to stakeholders
- **Mobile App Support**: Mobile-friendly report viewing
- **Data Visualization**: Interactive charts and graphs

## Support and Maintenance

### Regular Maintenance Tasks
- **Monthly**: Clear old cache entries and temporary files
- **Quarterly**: Review and update CSC field mappings
- **Annually**: Update CSC reporting requirements and formats

### Monitoring Recommendations
- **Error Logging**: Monitor application logs for report generation errors
- **Performance Metrics**: Track report generation times and cache hit rates
- **Usage Analytics**: Monitor which reports are generated most frequently

For technical support or questions about the CSC Reporting Service, please contact the development team or refer to the Laravel documentation for framework-specific issues.