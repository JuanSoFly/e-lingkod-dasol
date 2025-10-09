<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class CSCFormValidationService
{
    /**
     * Validate CSC Form No. 212 questionnaire data
     */
    public function validateQuestionnaire(array $data, $employeeId = null): array
    {
        $rules = $this->getQuestionnaireValidationRules();
        $messages = $this->getCscValidationMessages();

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate government ID format and date
     */
    public function validateGovernmentId(array $data): array
    {
        $rules = [
            'field_39_gov_id_number' => 'required|string|max:50',
            'field_39_gov_id_date_issued' => 'required|date|before_or_equal:today',
            'field_39_gov_id_place_issued' => 'required|string|max:100',
        ];

        $messages = [
            'field_39_gov_id_date_issued.before_or_equal' => 'Government ID date cannot be in the future.',
            'field_39_gov_id_date_issued.date' => 'Please provide a valid date format.',
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Format the date to mm/dd/yyyy format
        $validated = $validator->validated();
        if (isset($validated['field_39_gov_id_date_issued'])) {
            $validated['field_39_gov_id_date_issued'] = Carbon::parse($validated['field_39_gov_id_date_issued'])->format('m/d/Y');
        }

        return $validated;
    }

    /**
     * Validate salary grade format (00-0)
     */
    public function validateSalaryGrade(?string $salaryGrade): ?string
    {
        if (empty($salaryGrade)) {
            return null;
        }

        // Remove whitespace and convert to uppercase
        $salaryGrade = strtoupper(trim($salaryGrade));

        // Validate format: 2 digits, hyphen, 1 digit (e.g., "12-3", "01-0")
        if (!preg_match('/^\d{2}-\d{1}$/', $salaryGrade)) {
            throw ValidationException::withMessages([
                'salary_grade' => 'Salary grade must follow CSC format "00-0" (e.g., "12-3", "01-0").'
            ]);
        }

        return $salaryGrade;
    }

    /**
     * Validate date format (mm/dd/yyyy)
     */
    public function validateDateFormat($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            $carbonDate = Carbon::createFromFormat('m/d/Y', $date);

            // Ensure date is reasonable (not too far in past or future)
            if ($carbonDate->year < 1900 || $carbonDate->year > (date('Y') + 1)) {
                throw ValidationException::withMessages([
                    'date' => 'Please provide a valid date between 1900 and ' . (date('Y') + 1) . '.'
                ]);
            }

            return $carbonDate->format('m/d/Y');
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'date' => 'Date must be in mm/dd/yyyy format (e.g., "12/25/2023").'
            ]);
        }
    }

    /**
     * Validate work experience row count (maximum 25 rows per CSC)
     */
    public function validateWorkExperienceRowCount(int $count): void
    {
        if ($count > 25) {
            throw ValidationException::withMessages([
                'work_experience' => 'CSC Form No. 212 allows maximum of 25 work experience entries. Please remove older entries.'
            ]);
        }
    }

    /**
     * Validate reference count (exactly 3 references required)
     */
    public function validateReferenceCount(int $count): void
    {
        if ($count < 3) {
            throw ValidationException::withMessages([
                'references' => 'CSC Form No. 212 requires exactly 3 references. Please add ' . (3 - $count) . ' more reference(s).'
            ]);
        }

        if ($count > 3) {
            throw ValidationException::withMessages([
                'references' => 'CSC Form No. 212 allows only 3 references. Please remove ' . ($count - 3) . ' reference(s).'
            ]);
        }
    }

    /**
     * Check PDS completeness for CSC compliance
     */
    public function checkPdsCompleteness($employee): array
    {
        $missingFields = [];
        $warnings = [];

        // Check required personal information
        if (empty($employee->first_name)) $missingFields[] = 'First Name';
        if (empty($employee->last_name)) $missingFields[] = 'Last Name';
        if (empty($employee->birth_date)) $missingFields[] = 'Birth Date';
        if (empty($employee->gender)) $missingFields[] = 'Sex';
        if (empty($employee->civil_status)) $missingFields[] = 'Civil Status';
        if (empty($employee->citizenship)) $missingFields[] = 'Citizenship';

        // Check government IDs
        if (empty($employee->gsis_number) && empty($employee->philhealth_number) &&
            empty($employee->sss_number) && empty($employee->tin_number)) {
            $warnings[] = 'At least one government ID number (GSIS, PhilHealth, SSS, or TIN) should be provided.';
        }

        // Check addresses
        if (!$employee->residential_address || empty($employee->residential_address->city_municipality)) {
            $missingFields[] = 'Complete Residential Address';
        }

        // Check questionnaire completeness
        if (!$employee->questionnaire) {
            $missingFields[] = 'Questionnaire (Page 4 declarations)';
        } else {
            // Check if required fields 34-41 have answers using the new methods
            $requiredQuestions = ['q34_related', 'q35_charges', 'q36_candidate', 'q37_resignation', 'q38_immigrant', 'q41_indigenous', 'q41_pwd', 'q41_solo_parent'];

            foreach ($requiredQuestions as $questionCode) {
                if ($employee->questionnaire->getQuestionAnswer($questionCode) === null) {
                    $missingFields[] = 'Questionnaire Field ' . substr($questionCode, 1, 2);
                }
            }

            // Check government ID fields
            $requiredGovIdFields = ['field_39_gov_id_number', 'field_39_gov_id_date_issued', 'field_39_gov_id_place_issued'];
            foreach ($requiredGovIdFields as $field) {
                if (empty($employee->questionnaire->{$field})) {
                    $missingFields[] = 'Questionnaire Field 39 - ' . ucwords(str_replace('_', ' ', str_replace('field_39_', '', $field)));
                }
            }
        }

        // Check references (exactly 3 required)
        $referenceCount = $employee->references()->count();
        if ($referenceCount < 3) {
            $missingFields[] = 'References (' . (3 - $referenceCount) . ' more needed)';
        } elseif ($referenceCount > 3) {
            $warnings[] = 'Too many references provided (' . $referenceCount . ' found, only 3 allowed).';
        }

        return [
            'is_complete' => empty($missingFields),
            'missing_fields' => $missingFields,
            'warnings' => $warnings,
            'completion_percentage' => $this->calculateCompletionPercentage($employee)
        ];
    }

    /**
     * Calculate PDS completion percentage
     */
    private function calculateCompletionPercentage($employee): float
    {
        $totalFields = 20; // Approximate count of critical fields
        $completedFields = 0;

        // Basic info (7 fields)
        if ($employee->first_name) $completedFields++;
        if ($employee->last_name) $completedFields++;
        if ($employee->birth_date) $completedFields++;
        if ($employee->gender) $completedFields++;
        if ($employee->civil_status) $completedFields++;
        if ($employee->citizenship) $completedFields++;
        if ($employee->residential_address) $completedFields++;

        // Education (3 fields minimum)
        if ($employee->education()->count() > 0) $completedFields += 3;

        // Work experience (2 fields minimum)
        if ($employee->workExperiences()->count() > 0) $completedFields += 2;

        // Eligibility (1 field)
        if ($employee->pdsEligibilities()->count() > 0) $completedFields++;

        // References (3 fields)
        $refCount = min($employee->references()->count(), 3);
        $completedFields += $refCount;

        // Questionnaire (3 critical fields)
        if ($employee->questionnaire) {
            if ($employee->questionnaire->field_39_gov_id_number) $completedFields++;
            if ($employee->questionnaire->field_39_gov_id_date_issued) $completedFields++;
            if ($employee->questionnaire->field_39_gov_id_place_issued) $completedFields++;
        }

        return round(($completedFields / $totalFields) * 100, 1);
    }

    /**
     * Get questionnaire validation rules
     */
    private function getQuestionnaireValidationRules(): array
    {
        return [
            // Radio button answers (Fields 34-41)
            'q34_related' => 'required|boolean',
            'q35_charges' => 'required|boolean',
            'q36_candidate' => 'required|boolean',
            'q37_resignation' => 'required|boolean',
            'q38_immigrant' => 'required|boolean',
            'q41_indigenous' => 'required|boolean',
            'q41_pwd' => 'required|boolean',
            'q41_solo_parent' => 'required|boolean',

            // Government ID (Field 39 - always required)
            'field_39_gov_id_number' => 'required|string|max:50',
            'field_39_gov_id_date_issued' => 'required|date|before_or_equal:today',
            'field_39_gov_id_place_issued' => 'required|string|max:100',

            // Detail fields (required if corresponding answer is YES)
            'field_34_relationship' => 'nullable|string|required_if:q34_related,1|max:500',
            'field_35_charges' => 'nullable|string|required_if:q35_charges,1|max:1000',
            'field_36_candidate' => 'nullable|string|required_if:q36_candidate,1|max:200',
            'field_37_resignation' => 'nullable|string|required_if:q37_resignation,1|max:300',
            'field_38_immigrant' => 'nullable|string|required_if:q38_immigrant,1|max:100',

            // Special group memberships (optional but required if YES)
            'field_41_indigenous_member' => 'nullable|string|required_if:q41_indigenous,1|max:200',
            'field_41_pwd_member' => 'nullable|string|required_if:q41_pwd,1|max:200',
            'field_41_solo_parent_member' => 'nullable|string|required_if:q41_solo_parent,1|max:100',

            // Additional ID numbers (optional)
            'field_41_indigenous_id_number' => 'nullable|string|max:50',
            'field_41_pwd_id_number' => 'nullable|string|max:50',
            'field_41_solo_parent_id_number' => 'nullable|string|max:50',
        ];
    }

    /**
     * Get CSC-specific validation messages
     */
    private function getCscValidationMessages(): array
    {
        return [
            // Radio button validation messages
            'q34_related.required' => 'Field 34: Relationship to appointing authority is required.',
            'q34_related.boolean' => 'Field 34: Please select YES or NO.',
            'field_34_relationship.required_if' => 'Field 34: Please provide name and relationship details.',
            'field_34_relationship.max' => 'Field 34: Details should not exceed 500 characters.',

            'q35_charges.required' => 'Field 35: Administrative/criminal charges information is required.',
            'q35_charges.boolean' => 'Field 35: Please select YES or NO.',
            'field_35_charges.required_if' => 'Field 35: Please provide full details of charges.',
            'field_35_charges.max' => 'Field 35: Details should not exceed 1000 characters.',

            'q36_candidate.required' => 'Field 36: Election candidacy information is required.',
            'q36_candidate.boolean' => 'Field 36: Please select YES or NO.',
            'field_36_candidate.required_if' => 'Field 36: Please provide position and year details.',
            'field_36_candidate.max' => 'Field 36: Details should not exceed 200 characters.',

            'q37_resignation.required' => 'Field 37: Resignation to campaign information is required.',
            'q37_resignation.boolean' => 'Field 37: Please select YES or NO.',
            'field_37_resignation.required_if' => 'Field 37: Please provide candidate and political party details.',
            'field_37_resignation.max' => 'Field 37: Details should not exceed 300 characters.',

            'q38_immigrant.required' => 'Field 38: Immigrant status information is required.',
            'q38_immigrant.boolean' => 'Field 38: Please select YES or NO.',
            'field_38_immigrant.required_if' => 'Field 38: Please specify country.',
            'field_38_immigrant.max' => 'Field 38: Country name should not exceed 100 characters.',

            'q41_indigenous.required' => 'Field 41: Indigenous group membership is required.',
            'q41_indigenous.boolean' => 'Field 41: Please select YES or NO.',
            'field_41_indigenous_member.required_if' => 'Field 41: Please provide indigenous group and certificate details.',
            'field_41_indigenous_member.max' => 'Field 41: Details should not exceed 200 characters.',

            'q41_pwd.required' => 'Field 41: PWD status is required.',
            'q41_pwd.boolean' => 'Field 41: Please select YES or NO.',
            'field_41_pwd_member.required_if' => 'Field 41: Please provide disability and ID details.',
            'field_41_pwd_member.max' => 'Field 41: Details should not exceed 200 characters.',

            'q41_solo_parent.required' => 'Field 41: Solo parent status is required.',
            'q41_solo_parent.boolean' => 'Field 41: Please select YES or NO.',
            'field_41_solo_parent_member.required_if' => 'Field 41: Please provide Solo Parent ID number.',
            'field_41_solo_parent_member.max' => 'Field 41: ID number should not exceed 100 characters.',

            // Government ID validation messages
            'field_39_gov_id_number.required' => 'Field 39: Government ID number is required.',
            'field_39_gov_id_date_issued.required' => 'Field 39: Date issued is required.',
            'field_39_gov_id_place_issued.required' => 'Field 39: Place issued is required.',
            'field_39_gov_id_date_issued.before_or_equal' => 'Field 39: Date issued cannot be in the future.',
        ];
    }

    /**
     * Validate telephone number format
     */
    public function validateTelephoneNumber(?string $telephone): ?string
    {
        if (empty($telephone)) {
            return null;
        }

        // Remove common formatting characters
        $clean = preg_replace('/[\s\-\(\)]+/', '', $telephone);

        // Validate format: 7-11 digits, optional area code
        if (!preg_match('/^(\d{7,11}|\d{3,4}\d{7})$/', $clean)) {
            throw ValidationException::withMessages([
                'telephone' => 'Telephone number must be a valid format (e.g., "02-1234-5678" or "09123456789").'
            ]);
        }

        return $telephone;
    }

    /**
     * Validate mobile number format
     */
    public function validateMobileNumber(?string $mobile): ?string
    {
        if (empty($mobile)) {
            return null;
        }

        // Remove common formatting characters
        $clean = preg_replace('/[\s\-\(\)]+/', '', $mobile);

        // Validate Philippine mobile number format (10-11 digits starting with 09)
        if (!preg_match('/^09\d{8,9}$/', $clean)) {
            throw ValidationException::withMessages([
                'mobile' => 'Mobile number must be a valid Philippine format (e.g., "09123456789").'
            ]);
        }

        return $mobile;
    }

    /**
     * Validate email format for government use
     */
    public function validateGovernmentEmail(string $email): string
    {
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email:rfc,dns|max:100'
        ], [
            'email.email' => 'Please provide a valid email address that can receive emails.',
            'email.max' => 'Email address should not exceed 100 characters.',
            'email.dns' => 'Email domain must be valid and able to receive emails.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return strtolower($email);
    }
}