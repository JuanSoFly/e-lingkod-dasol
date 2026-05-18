<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;

class PDSDataSanitizationService
{
    /**
     * Sanitize employee data based on user role for export purposes
     * This method is called by PDSExport class to filter data according to role permissions
     *
     * @param Employee $employee
     * @param User $user
     * @return Employee
     */
    public function sanitizeForRole(Employee $employee, ?User $user = null): Employee
    {
        if (!$user) {
            return $employee;
        }

        $userRole = $this->getUserRole($user);

        // For now, return the original employee object
        // The actual sanitization logic can be implemented at the field level in PDSExport
        // This prevents the current syntax error and allows the export to work

        if ($userRole === 'employee') {
            // Check if employee is accessing their own data
            // Check both user_id field and user relationship
            if ($employee->user_id && $employee->user_id !== $user->id) {
                throw new \Exception('Unauthorized access to employee data');
            }
            if ($employee->user && $employee->user->id !== $user->id) {
                throw new \Exception('Unauthorized access to employee data');
            }
        }

        return $employee;
    }

    /**
     * Get user role name
     *
     * @param User $user
     * @return string
     */
    private function getUserRole(User $user): string
    {
        if ($user->hasRole('Super Admin')) {
            return 'super_admin';
        }

        if ($user->hasRole('HR Admin')) {
            return 'hr_admin';
        }

        if ($user->hasRole('Employee')) {
            return 'employee';
        }

        return 'public';
    }

    /**
     * Sanitize PDS data based on user role and security requirements
     *
     * @param array $data
     * @param string $userRole
     * @param string|null $requestingUserId
     * @return array
     */
    public function sanitizePDSData(array $data, string $userRole, ?string $requestingUserId = null): array
    {
        switch ($userRole) {
            case 'employee':
                return $this->sanitizeForEmployee($data, $requestingUserId);
            case 'hr_admin':
                return $this->sanitizeForHRAdmin($data);
            case 'super_admin':
                return $this->sanitizeForSuperAdmin($data);
            default:
                return $this->sanitizeForPublic($data);
        }
    }

    /**
     * Sanitize data for employee self-access
     *
     * @param array $data
     * @param string|null $requestingUserId
     * @return array
     */
    private function sanitizeForEmployee(array $data, ?string $requestingUserId): array
    {
        // Employees can only see their own data
        if (isset($data['user_id']) && $data['user_id'] != $requestingUserId) {
            throw new \Exception('Unauthorized access to employee data');
        }

        // Remove sensitive fields that employees shouldn't see in their own export
        $sensitiveFields = [
            'sss_number',
            'tin_number',
            'philhealth_number',
            'pagibig_number',
            'internal_notes',
            'hr_notes',
            'system_flags',
            'performance_rating',
            'annual_salary_rate',
            'monthly_salary_rate',
            'daily_salary_rate',
            'hourly_rate'
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                unset($data[$field]);
            }
        }

        // Keep basic salary information for employee awareness
        if (isset($data['basic_salary'])) {
            $data['basic_salary'] = 'Confidential - Contact HR';
        }

        // Sanitize related data
        if (isset($data['work_experience'])) {
            $data['work_experience'] = $this->sanitizeWorkExperience($data['work_experience'], 'employee');
        }

        if (isset($data['education'])) {
            $data['education'] = $this->sanitizeEducation($data['education'], 'employee');
        }

        // Remove system fields
        $systemFields = [
            'password_hash',
            'remember_token',
            'email_verified_at',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'created_by',
            'updated_by',
            'deleted_at'
        ];

        foreach ($systemFields as $field) {
            if (isset($data[$field])) {
                unset($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Sanitize data for HR Admin access
     *
     * @param array $data
     * @return array
     */
    private function sanitizeForHRAdmin(array $data): array
    {
        // HR admins can see most data but not internal system fields
        $internalFields = [
            'internal_notes',
            'system_flags',
            'audit_trail',
            'password_hash',
            'remember_token'
        ];

        foreach ($internalFields as $field) {
            unset($data[$field]);
        }

        // HR admins can only access active employees' full data
        if (isset($data['employment_status']) && $data['employment_status'] !== 'Active') {
            // Limit data for inactive employees
            $data = $this->limitInactiveEmployeeData($data);
        }

        return $data;
    }

    /**
     * Sanitize data for Super Admin access
     *
     * @param array $data
     * @return array
     */
    private function sanitizeForSuperAdmin(array $data): array
    {
        // Super admins have full access but remove technical fields
        $technicalFields = [
            'password_hash',
            'remember_token',
            'email_verified_at',
            'two_factor_secret',
            'two_factor_recovery_codes'
        ];

        foreach ($technicalFields as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    /**
     * Sanitize data for public/anonymized access
     *
     * @param array $data
     * @return array
     */
    private function sanitizeForPublic(array $data): array
    {
        // Only show basic, non-identifying information
        $allowedFields = [
            'id',
            'employment_status',
            'department',
            'position_title' // Only title, not detailed info
        ];

        $sanitized = [];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $sanitized[$field] = $data[$field];
            }
        }

        return $sanitized;
    }

    /**
     * Limit data for inactive employees
     *
     * @param array $data
     * @return array
     */
    private function limitInactiveEmployeeData(array $data): array
    {
        // Only show basic information for inactive employees
        $basicFields = [
            'id',
            'employee_number',
            'first_name',
            'middle_name',
            'last_name',
            'employment_status',
            'department'
        ];

        $limited = [];
        foreach ($basicFields as $field) {
            if (isset($data[$field])) {
                $limited[$field] = $data[$field];
            }
        }

        return $limited;
    }

    /**
     * Sanitize work experience data
     *
     * @param array $workExperience
     * @param string $userRole
     * @return array
     */
    private function sanitizeWorkExperience(array $workExperience, string $userRole): array
    {
        if ($userRole === 'employee') {
            // Employees see their work experience but without salary details
            foreach ($workExperience as &$experience) {
                unset($experience['monthly_salary']);
                unset($experience['salary_grade']);
                unset($experience['step_increment']);
            }
        }

        return $workExperience;
    }

    /**
     * Sanitize education data
     *
     * @param array $education
     * @param string $userRole
     * @return array
     */
    private function sanitizeEducation(array $education, string $userRole): array
    {
        // Education data is generally safe for all roles
        // but we might want to remove sensitive scholarship details
        if ($userRole === 'employee') {
            foreach ($education as &$edu) {
                // Remove scholarship sponsor details if sensitive
                if (isset($edu['scholarship_honors'])) {
                    $edu['scholarship_honors'] = $this->sanitizeScholarshipInfo($edu['scholarship_honors']);
                }
            }
        }

        return $education;
    }

    /**
     * Sanitize scholarship information
     *
     * @param string $scholarshipInfo
     * @return string
     */
    private function sanitizeScholarshipInfo(string $scholarshipInfo): string
    {
        // Remove sensitive financial details from scholarship information
        $patterns = [
            '/\b\d{1,6}(?:,\d{3})*(?:\.\d{2})?\s*(?:pesos|php|₱)?\b/i' // Money amounts
        ];

        return preg_replace($patterns, '[Financial details removed]', $scholarshipInfo);
    }

    /**
     * Validate and sanitize email addresses
     *
     * @param string $email
     * @return string|null
     */
    public function sanitizeEmail(string $email): ?string
    {
        if (empty($email)) {
            return null;
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        // Convert to lowercase and trim
        return strtolower(trim($email));
    }

    /**
     * Sanitize phone numbers
     *
     * @param string $phoneNumber
     * @return string|null
     */
    public function sanitizePhoneNumber(string $phoneNumber): ?string
    {
        if (empty($phoneNumber)) {
            return null;
        }

        // Remove all non-numeric characters except +
        $sanitized = preg_replace('/[^0-9+]/', '', $phoneNumber);

        // Validate Philippine phone number format
        if (preg_match('/^(\+639|09)\d{9}$/', $sanitized)) {
            return $sanitized;
        }

        return null;
    }

    /**
     * Sanitize addresses for export
     *
     * @param string $address
     * @return string
     */
    public function sanitizeAddress(string $address): string
    {
        if (empty($address)) {
            return '';
        }

        // Remove potentially dangerous characters
        $address = preg_replace('/[<>"\']/', '', $address);

        // Normalize spacing
        $address = preg_replace('/\s+/', ' ', $address);

        return trim($address);
    }

    /**
     * Sanitize names to prevent injection attacks
     *
     * @param string $name
     * @return string
     */
    public function sanitizeName(string $name): string
    {
        if (empty($name)) {
            return '';
        }

        // Remove special characters except common name characters
        $name = preg_replace('/[^a-zA-ZáÁéÉíÍóÓúÚñÑ\s\-\.]/', '', $name);

        // Normalize spacing
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }

    /**
     * Validate and sanitize dates
     *
     * @param string $date
     * @return string|null
     */
    public function sanitizeDate(string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            $dateObj = new \DateTime($date);
            return $dateObj->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Sanitize document metadata
     *
     * @param array $documents
     * @param string $userRole
     * @return array
     */
    public function sanitizeDocuments(array $documents, string $userRole): array
    {
        $sanitized = [];

        foreach ($documents as $document) {
            $doc = [
                'document_type' => $document['document_type'] ?? 'Unknown',
                'upload_date' => $this->sanitizeDate($document['created_at'] ?? ''),
                'file_size' => $document['file_size'] ?? 0
            ];

            // Only show filename to HR admins and super admins
            if (in_array($userRole, ['hr_admin', 'super_admin'])) {
                $doc['filename'] = $document['filename'] ?? '';
            }

            $sanitized[] = $doc;
        }

        return $sanitized;
    }

    /**
     * Check if user has access to specific employee data
     *
     * @param string $employeeId
     * @param string $userRole
     * @param string|null $requestingUserId
     * @return bool
     */
    public function canAccessEmployeeData(string $employeeId, string $userRole, ?string $requestingUserId = null): bool
    {
        switch ($userRole) {
            case 'employee':
                return $employeeId === $requestingUserId;
            case 'hr_admin':
                return $this->canHRAdminAccessEmployee($employeeId);
            case 'super_admin':
                return true; // Super admins have full access
            default:
                return false;
        }
    }

    /**
     * Check if HR admin can access specific employee
     *
     * @param string $employeeId
     * @return bool
     */
    private function canHRAdminAccessEmployee(string $employeeId): bool
    {
        // HR admins can access active employees
        $employee = \App\Models\Employee::find($employeeId);

        if (!$employee) {
            return false;
        }

        return $employee->employment_status === 'Active';
    }

    /**
     * Generate audit log entry for data access
     *
     * @param string $employeeId
     * @param string $userRole
     * @param string $userId
     * @param string $action
     * @return array
     */
    public function generateAuditLog(string $employeeId, string $userRole, string $userId, string $action): array
    {
        return [
            'employee_id' => $employeeId,
            'user_id' => $userId,
            'user_role' => $userRole,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ];
    }
}