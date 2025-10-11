<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeFamilyBackground;
use App\Models\EmployeeChildren;
use App\Models\EmployeeCivilServiceEligibility;
use App\Models\EmployeeVoluntaryWork;
use App\Models\EmployeeOtherInformation;
use App\Models\EmployeeReference;
use App\Models\EmployeeQuestionnaire;
use App\Models\EmployeeWorkExperience;
use App\Models\EmployeeTraining;
use App\Models\EmployeePhoto;
use App\Http\Controllers\Controller;
use App\Services\CSCFormValidationService;
use App\Rules\CscDateFormat;
use App\Rules\GovernmentIdFormat;
use App\Rules\SalaryGradeFormat;
use App\Rules\TelephoneNumberFormat;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Barryvdh\DomPDF\Facade\Pdf;

class PDSController extends Controller
{
    use AuthorizesRequests;

    /**
     * Authorize PDS access - Employees can only access their own PDS, HR/Admin can access all
     */
    private function authorizePdsAccess(Employee $employee, string $action = 'view')
    {
        if (auth()->user()->hasRole('Employee')) {
            if (auth()->user()->employee?->id !== $employee->id) {
                abort(403, 'You can only access your own PDS.');
            }
        } else {
            $this->authorize($action, $employee);
        }
    }

    public function dashboard(Employee $employee)
    {
        // Authorization: Employees can only view their own PDS, HR/Admin can view all
        if (auth()->user()->hasRole('Employee')) {
            if (auth()->user()->employee?->id !== $employee->id) {
                abort(403, 'You can only view your own PDS.');
            }
        } else {
            $this->authorizePdsAccess($employee, 'view');
        }

        $completionStatus = $employee->getPdsCompletionStatus();

        return view('pds.dashboard', compact('employee', 'completionStatus'));
    }

    // Panel 1: Personal Information
    public function personalInformation(Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'view');

        return view('pds.personal-information', compact('employee'));
    }

    public function updatePersonalInformation(Request $request, Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'update');

        // Initialize CSC Form Validation Service
        $cscValidator = new CSCFormValidationService();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'name_extension' => 'nullable|string|max:10',
            'birth_date' => [
                'required',
                'date',
                new CscDateFormat()
            ],
            'place_of_birth' => 'nullable|string|max:255',
            'gender' => 'required|in:Male,Female',
            'civil_status' => 'required|in:Single,Married,Widowed,Separated,Other',
            'civil_status_other_details' => 'nullable|string|max:255',
            'citizenship' => 'required|in:Filipino,Dual Citizenship',
            'dual_citizenship_type' => 'nullable|in:By Birth,By Naturalization',
            'dual_citizenship_country' => 'nullable|string|max:255',
            'height' => 'nullable|string|max:10',
            'weight' => 'nullable|string|max:10',
            'blood_type' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            // Enhanced Government IDs validation with CSC format compliance
            'gsis_number' => [
                'nullable',
                'string',
                'max:50',
                new GovernmentIdFormat('gsis')
            ],
            'pagibig_number' => [
                'nullable',
                'string',
                'max:50',
                new GovernmentIdFormat('pagibig')
            ],
            'philhealth_number' => [
                'nullable',
                'string',
                'max:50',
                new GovernmentIdFormat('philhealth')
            ],
            'sss_number' => [
                'nullable',
                'string',
                'max:50',
                new GovernmentIdFormat('sss')
            ],
            'tin_number' => [
                'nullable',
                'string',
                'max:50',
                new GovernmentIdFormat('tin')
            ],
            'agency_employee_no' => 'nullable|string|max:50',
            // Addresses
            'res_house_block_lot_no' => 'nullable|string|max:255',
            'res_street' => 'nullable|string|max:255',
            'res_subdivision_village' => 'nullable|string|max:255',
            'res_barangay' => 'nullable|string|max:255',
            'res_city_municipality' => 'nullable|string|max:255',
            'res_province' => 'nullable|string|max:255',
            'res_zip_code' => 'nullable|string|max:10',
            'perm_house_block_lot_no' => 'nullable|string|max:255',
            'perm_street' => 'nullable|string|max:255',
            'perm_subdivision_village' => 'nullable|string|max:255',
            'perm_barangay' => 'nullable|string|max:255',
            'perm_city_municipality' => 'nullable|string|max:255',
            'perm_province' => 'nullable|string|max:255',
            'perm_zip_code' => 'nullable|string|max:10',
            // Contact Info with enhanced telephone validation
            'telephone_no' => [
                'nullable',
                'string',
                'max:20',
                new TelephoneNumberFormat()
            ],
            'mobile_no' => [
                'nullable',
                'string',
                'max:20',
                new TelephoneNumberFormat()
            ],
            'email' => 'required|email|unique:employees,email,' . $employee->id,
        ]);

        // Perform additional CSC validation for government IDs
        $govIdData = [
            'gsis_number' => $validated['gsis_number'] ?? null,
            'pagibig_number' => $validated['pagibig_number'] ?? null,
            'philhealth_number' => $validated['philhealth_number'] ?? null,
            'sss_number' => $validated['sss_number'] ?? null,
            'tin_number' => $validated['tin_number'] ?? null,
        ];

        $govIdValidation = $cscValidator->validateGovernmentId($govIdData);

        if (!empty($govIdValidation['errors'])) {
            return redirect()->back()
                ->withInput()
                ->withErrors($govIdValidation['errors']);
        }

        $employee->update($validated);

        return redirect()->route('pds.dashboard', $employee)
            ->with('success', 'Personal information updated successfully.');
    }

    // Panel 2: Family Background
    public function familyBackground(Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'view');

        $familyBackground = $employee->familyBackground;
        $children = $employee->children;

        return view('pds.family-background', compact('employee', 'familyBackground', 'children'));
    }

    public function updateFamilyBackground(Request $request, Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'update');

        $validated = $request->validate([
            // Spouse information with enhanced validation
            'spouse_surname' => 'nullable|string|max:255',
            'spouse_first_name' => 'nullable|string|max:255',
            'spouse_middle_name' => 'nullable|string|max:255',
            'spouse_occupation' => 'nullable|string|max:255',
            'spouse_employer' => 'nullable|string|max:255',
            'spouse_business_address' => 'nullable|string',
            'spouse_telephone_no' => [
                'nullable',
                'string',
                'max:20',
                new TelephoneNumberFormat()
            ],
            'spouse_salary_grade' => [
                'nullable',
                'string',
                'max:10',
                new SalaryGradeFormat()
            ],
            // Parents information
            'father_surname' => 'nullable|string|max:255',
            'father_first_name' => 'nullable|string|max:255',
            'father_middle_name' => 'nullable|string|max:255',
            'mother_maiden_name' => 'nullable|string|max:255',
            'mother_surname' => 'nullable|string|max:255',
            'mother_first_name' => 'nullable|string|max:255',
            'mother_middle_name' => 'nullable|string|max:255',
            // Children
            'children' => 'nullable|array',
            'children.*.full_name' => 'required|string|max:255',
            'children.*.date_of_birth' => 'required|date',
        ]);

        DB::transaction(function () use ($employee, $validated) {
            // Update or create family background
            $employee->familyBackground()->updateOrCreate(
                ['employee_id' => $employee->id],
                array_filter([
                    'spouse_surname' => $validated['spouse_surname'] ?? null,
                    'spouse_first_name' => $validated['spouse_first_name'] ?? null,
                    'spouse_middle_name' => $validated['spouse_middle_name'] ?? null,
                    'spouse_occupation' => $validated['spouse_occupation'] ?? null,
                    'spouse_employer' => $validated['spouse_employer'] ?? null,
                    'spouse_business_address' => $validated['spouse_business_address'] ?? null,
                    'spouse_telephone_no' => $validated['spouse_telephone_no'] ?? null,
                    'father_surname' => $validated['father_surname'] ?? null,
                    'father_first_name' => $validated['father_first_name'] ?? null,
                    'father_middle_name' => $validated['father_middle_name'] ?? null,
                    'mother_maiden_name' => $validated['mother_maiden_name'] ?? null,
                    'mother_surname' => $validated['mother_surname'] ?? null,
                    'mother_first_name' => $validated['mother_first_name'] ?? null,
                    'mother_middle_name' => $validated['mother_middle_name'] ?? null,
                ], function($value) {
                    return $value !== null && $value !== '';
                })
            );

            // Handle spouse salary grade separately if the field doesn't exist in database
            if (!empty($validated['spouse_salary_grade'])) {
                $familyBackground = $employee->familyBackground;
                if ($familyBackground) {
                    // Add spouse salary grade to additional_info field or similar
                    $additionalInfo = $familyBackground->additional_info ?? [];
                    $additionalInfo['spouse_salary_grade'] = $validated['spouse_salary_grade'];
                    $familyBackground->additional_info = $additionalInfo;
                    $familyBackground->save();
                }
            }

            // Update children
            if (!empty($validated['children'])) {
                // Delete existing children
                $employee->children()->delete();

                // Add new children
                foreach ($validated['children'] as $child) {
                    $employee->children()->create($child);
                }
            }
        });

        return redirect()->route('pds.dashboard', $employee)
            ->with('success', 'Family background updated successfully.');
    }

    // Panel 4: Civil Service Eligibility
    public function eligibility(Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'view');

        $eligibilities = $employee->pdsEligibilities;

        return view('pds.eligibility', compact('employee', 'eligibilities'));
    }

    public function storeEligibility(Request $request, Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'update');

        // Initialize CSC Form Validation Service
        $cscValidator = new CSCFormValidationService();

        $validated = $request->validate([
            'eligibility_name' => 'required|string|max:255',
            'rating' => 'nullable|numeric|min:0|max:100',
            'date_of_examination' => [
                'nullable',
                'date',
                new CscDateFormat()
            ],
            'place_of_examination' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:255',
            'date_of_validity' => [
                'nullable',
                'date',
                new CscDateFormat()
            ],
        ]);

        $employee->pdsEligibilities()->create($validated);

        return redirect()->route('pds.eligibility', $employee)
            ->with('success', 'Civil service eligibility added successfully.');
    }

    public function destroyEligibility(Employee $employee, EmployeeCivilServiceEligibility $eligibility)
    {
        $this->authorizePdsAccess($employee, 'update');

        if ($eligibility->employee_id !== $employee->id) {
            abort(404);
        }

        $eligibility->delete();

        return redirect()->route('pds.eligibility', $employee)
            ->with('success', 'Civil service eligibility deleted successfully.');
    }

    // Panel 6: Voluntary Work
    public function voluntaryWork(Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'view');

        $voluntaryWork = $employee->voluntaryWork;

        return view('pds.voluntary-work', compact('employee', 'voluntaryWork'));
    }

    public function storeVoluntaryWork(Request $request, Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'update');

        // Initialize CSC Form Validation Service
        $cscValidator = new CSCFormValidationService();

        $validated = $request->validate([
            'organization_name_address' => 'required|string',
            'inclusive_date_from' => [
                'required',
                'date',
                new CscDateFormat()
            ],
            'inclusive_date_to' => [
                'nullable',
                'date',
                'after_or_equal:inclusive_date_from',
                new CscDateFormat()
            ],
            'number_of_hours' => 'required|integer|min:1',
            'position_nature_of_work' => 'required|string',
        ]);

        $employee->voluntaryWork()->create($validated);

        return redirect()->route('pds.voluntary-work', $employee)
            ->with('success', 'Voluntary work experience added successfully.');
    }

    public function destroyVoluntaryWork(Employee $employee, EmployeeVoluntaryWork $voluntaryWork)
    {
        $this->authorizePdsAccess($employee, 'update');

        if ($voluntaryWork->employee_id !== $employee->id) {
            abort(404);
        }

        $voluntaryWork->delete();

        return redirect()->route('pds.voluntary-work', $employee)
            ->with('success', 'Voluntary work experience deleted successfully.');
    }

    // Panel 5: Work Experience
    public function workExperience(Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'view');

        $workExperiences = $employee->workExperiences;

        return view('pds.work-experience', compact('employee', 'workExperiences'));
    }

    public function storeWorkExperience(Request $request, Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'update');

        // Initialize CSC Form Validation Service
        $cscValidator = new CSCFormValidationService();

        $validated = $request->validate([
            'inclusive_date_from' => [
                'required',
                'date',
                new CscDateFormat()
            ],
            'inclusive_date_to' => [
                'nullable',
                'date',
                'after_or_equal:inclusive_date_from',
                new CscDateFormat()
            ],
            'position_title' => 'required|string|max:255',
            'department_agency_office' => 'required|string|max:255',
            'monthly_salary' => 'nullable|numeric|min:0',
            'salary_grade_step' => [
                'nullable',
                'string',
                'max:50',
                new SalaryGradeFormat()
            ],
            'status_of_appointment' => 'required|in:Permanent,Temporary,Casual,Contractual,Job Order,Contract of Service',
            'is_government_service' => 'required|boolean',
        ]);

        // Validate work experience count for CSC compliance
        $currentWorkExperienceCount = $employee->workExperiences()->count();
        $cscValidator->validateWorkExperienceRowCount($currentWorkExperienceCount + 1);

        // Map PDS fields to original required fields for compatibility
        $workExperienceData = $validated;
        $workExperienceData['position'] = $validated['position_title'];
        $workExperienceData['company'] = $validated['department_agency_office'];
        $workExperienceData['from_date'] = $validated['inclusive_date_from'];
        $workExperienceData['to_date'] = $validated['inclusive_date_to'];
        $workExperienceData['salary'] = $validated['monthly_salary'];
        $workExperienceData['status'] = $validated['status_of_appointment'];

        $employee->workExperiences()->create($workExperienceData);

        return redirect()->route('pds.work-experience', $employee)
            ->with('success', 'Work experience added successfully.');
    }

    public function destroyWorkExperience(Employee $employee, EmployeeWorkExperience $workExperience)
    {
        $this->authorizePdsAccess($employee, 'update');

        if ($workExperience->employee_id !== $employee->id) {
            abort(404);
        }

        $workExperience->delete();

        return redirect()->route('pds.work-experience', $employee)
            ->with('success', 'Work experience deleted successfully.');
    }

    // Panel 7: Learning & Development
    public function learningDevelopment(Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'view');

        $trainings = $employee->trainings;

        return view('pds.learning-development', compact('employee', 'trainings'));
    }

    public function storeLearningDevelopment(Request $request, Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'update');

        // Initialize CSC Form Validation Service
        $cscValidator = new CSCFormValidationService();

        $validated = $request->validate([
            'training_title' => 'required|string|max:255',
            'inclusive_date_from' => [
                'required',
                'date',
                new CscDateFormat()
            ],
            'inclusive_date_to' => [
                'nullable',
                'date',
                'after_or_equal:inclusive_date_from',
                new CscDateFormat()
            ],
            'number_of_hours' => 'required|numeric|min:0',
            'type_of_ld' => 'required|in:Managerial,Supervisory,Technical,Professional,Foundational,Other',
            'conducted_sponsored_by' => 'required|string|max:255',
            'attachment_id' => 'nullable|string|max:255',
        ]);

        $employee->trainings()->create($validated);

        return redirect()->route('pds.learning-development', $employee)
            ->with('success', 'Learning & Development intervention added successfully.');
    }

    public function destroyLearningDevelopment(Employee $employee, EmployeeTraining $training)
    {
        $this->authorizePdsAccess($employee, 'update');

        if ($training->employee_id !== $employee->id) {
            abort(404);
        }

        $training->delete();

        return redirect()->route('pds.learning-development', $employee)
            ->with('success', 'Learning & Development intervention deleted successfully.');
    }

    // Panel 8: Other Information
    public function otherInformation(Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'view');

        $specialSkills = $employee->specialSkills;
        $distinctions = $employee->distinctions;
        $memberships = $employee->memberships;

        return view('pds.other-information', compact('employee', 'specialSkills', 'distinctions', 'memberships'));
    }

    public function storeOtherInformation(Request $request, Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'update');

        $validated = $request->validate([
            'information_type' => 'required|in:special_skills,distinctions,memberships',
            'description' => 'required|string',
        ]);

        $employee->otherInformation()->create($validated);

        return redirect()->route('pds.other-information', $employee)
            ->with('success', 'Information added successfully.');
    }

    public function destroyOtherInformation(Employee $employee, EmployeeOtherInformation $otherInformation)
    {
        $this->authorizePdsAccess($employee, 'update');

        if ($otherInformation->employee_id !== $employee->id) {
            abort(404);
        }

        $otherInformation->delete();

        return redirect()->route('pds.other-information', $employee)
            ->with('success', 'Information deleted successfully.');
    }

    // Panel 9: References
    public function references(Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'view');

        $references = $employee->references;

        return view('pds.references', compact('employee', 'references'));
    }

    public function storeReference(Request $request, Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'update');

        // Initialize CSC Form Validation Service
        $cscValidator = new CSCFormValidationService();

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'address' => 'required|string',
            'telephone_no' => [
                'nullable',
                'string',
                'max:20',
                new TelephoneNumberFormat()
            ],
        ]);

        // Validate reference count for CSC compliance
        $currentReferenceCount = $employee->references()->count();
        $cscValidator->validateReferenceCount($currentReferenceCount + 1);

        $employee->references()->create($validated);

        return redirect()->route('pds.references', $employee)
            ->with('success', 'Reference added successfully.');
    }

    public function destroyReference(Employee $employee, EmployeeReference $reference)
    {
        $this->authorizePdsAccess($employee, 'update');

        if ($reference->employee_id !== $employee->id) {
            abort(404);
        }

        $reference->delete();

        return redirect()->route('pds.references', $employee)
            ->with('success', 'Reference deleted successfully.');
    }

    // Panel 10: Questionnaire
public function questionnaire(Employee $employee)
{
    $this->authorizePdsAccess($employee, 'view');

    $questionnaire = $employee->questionnaire()->firstOrNew();
    $cscFieldLabels = EmployeeQuestionnaire::getCscFieldLabels();

    // Calculate completion percentage
    $completionPercentage = $questionnaire ? $questionnaire->getCompletionPercentage() : 0;

    // Share empty $errors variable for @error directive compatibility
    // Always ensure we have a ViewErrorBag, not a regular MessageBag
    session()->put('errors', new ViewErrorBag());

    return view('pds.questionnaire', compact('employee', 'questionnaire', 'cscFieldLabels', 'completionPercentage'));
}

public function updateQuestionnaire(Request $request, Employee $employee)
{
    $this->authorizePdsAccess($employee, 'update');

    // Enable comprehensive logging for debugging
    \Log::info('PDS updateQuestionnaire - Starting process', [
        'employee_id' => $employee->id,
        'user_id' => auth()->id(),
        'request_method' => $request->method(),
        'request_url' => $request->fullUrl(),
        'incoming_data' => $request->all(),
        'timestamp' => now()->toDateTimeString()
    ]);

    // Enable query logging for database operations
    \DB::enableQueryLog();

    try {
        // Base validation rules
        $rules = [
            // CSC Form 212 - Field 34: Relationship to appointing authority
            'field_34_yes_no' => 'required|boolean|in:0,1',
            'field_34_relationship_details' => 'nullable|string|max:1000',
            'field_34b_yes_no' => 'required|boolean|in:0,1',
            'field_34b_relationship_details' => 'nullable|string|max:1000',

            // CSC Form 212 - Field 35: Administrative/criminal charges
            'field_35a_yes_no' => 'required|boolean|in:0,1',
            'field_35_administrative_offense_details' => 'nullable|string|max:1000',
            'field_35b_yes_no' => 'required|boolean|in:0,1',
            'field_36_criminal_charge_details' => 'nullable|string|max:1000',

            // CSC Form 212 - Field 36: Conviction of any crime
            'field_36_yes_no' => 'required|boolean|in:0,1',
            'field_36_conviction_details' => 'nullable|string|max:1000',

            // CSC Form 212 - Field 37: Separation from service
            'field_37_yes_no' => 'required|boolean|in:0,1',
            'field_37_separation_details' => 'nullable|string|max:1000',

            // CSC Form 212 - Field 38: Election candidacy and resignation
            'field_38a_yes_no' => 'required|boolean|in:0,1',
            'field_36_candidate_details' => 'nullable|string|max:1000',
            'field_38b_yes_no' => 'required|boolean|in:0,1',
            'field_37_resignation_details' => 'nullable|string|max:1000',

            // CSC Form 212 - Field 39: Immigrant status
            'field_39_yes_no' => 'required|boolean|in:0,1',
            'field_38_immigrant_status' => 'nullable|string|max:255',
            'field_39_immigrant_details' => 'nullable|string|max:1000',

            // CSC Form 212 - Field 40: Indigenous/PWD/Solo Parent status
            'field_40a_yes_no' => 'required|boolean|in:0,1',
            'field_40b_yes_no' => 'required|boolean|in:0,1',
            'field_40c_yes_no' => 'required|boolean|in:0,1',
            'field_40_indigenous_details' => 'nullable|string|max:500',
            'field_40_pwd_details' => 'nullable|string|max:500',
            'field_40_solo_parent_details' => 'nullable|string|max:500',
        ];

        // Add custom validation messages for better user experience
        $customMessages = [
            'field_34_yes_no.required' => 'Please answer question 34a about relationship to appointing authority.',
            'field_34b_yes_no.required' => 'Please answer question 34b about relationship within fourth degree.',
            'field_35a_yes_no.required' => 'Please answer question 35a about administrative offense.',
            'field_35b_yes_no.required' => 'Please answer question 35b about criminal charge.',
            'field_36_yes_no.required' => 'Please answer question 36 about criminal conviction.',
            'field_37_yes_no.required' => 'Please answer question 37 about separation from service.',
            'field_38a_yes_no.required' => 'Please answer question 38a about election candidacy.',
            'field_38b_yes_no.required' => 'Please answer question 38b about resignation to campaign.',
            'field_39_yes_no.required' => 'Please answer question 39 about immigrant status.',
            'field_40a_yes_no.required' => 'Please answer question 40a about indigenous group membership.',
            'field_40b_yes_no.required' => 'Please answer question 40b about PWD status.',
            'field_40c_yes_no.required' => 'Please answer question 40c about solo parent status.',
            '*.in' => 'Please select either Yes or No for all required questions.',
        ];

        // Log validation start
        \Log::info('PDS updateQuestionnaire - Starting validation', [
            'employee_id' => $employee->id,
            'rules_count' => count($rules),
            'timestamp' => now()->toDateTimeString()
        ]);

        // Validate the basic requirements first
        $validated = $request->validate($rules, $customMessages);

        \Log::info('PDS updateQuestionnaire - Validation passed', [
            'employee_id' => $employee->id,
            'validated_fields' => array_keys($validated),
            'timestamp' => now()->toDateTimeString()
        ]);

        // Improved boolean conversion - handle radio button string to boolean conversion
        $booleanFields = [
            'field_34_yes_no', 'field_34b_yes_no', 'field_35a_yes_no', 'field_35b_yes_no', 'field_36_yes_no',
            'field_37_yes_no', 'field_38a_yes_no', 'field_38b_yes_no', 'field_39_yes_no',
            'field_40a_yes_no', 'field_40b_yes_no', 'field_40c_yes_no'
        ];

        // Convert boolean fields with proper logging
        foreach ($booleanFields as $field) {
            if (isset($validated[$field])) {
                $originalValue = $validated[$field];
                $validated[$field] = $validated[$field] === '1' || $validated[$field] === 1 ? true : false;

                \Log::debug('PDS updateQuestionnaire - Boolean conversion', [
                    'employee_id' => $employee->id,
                    'field' => $field,
                    'original_value' => $originalValue,
                    'converted_value' => $validated[$field],
                    'timestamp' => now()->toDateTimeString()
                ]);
            }
        }

        \Log::info('PDS updateQuestionnaire - Boolean conversion completed', [
            'employee_id' => $employee->id,
            'converted_fields_count' => count($booleanFields),
            'timestamp' => now()->toDateTimeString()
        ]);

        // Add conditional validation for detail fields when "YES" is selected (after boolean conversion)
        $conditionalErrors = [];

        if ($validated['field_34_yes_no'] && empty($validated['field_34_relationship_details'])) {
            $conditionalErrors['field_34_relationship_details'] = 'Please provide details for question 34a.';
        }

        if ($validated['field_34b_yes_no'] && empty($validated['field_34b_relationship_details'])) {
            $conditionalErrors['field_34b_relationship_details'] = 'Please provide details for question 34b.';
        }

        if ($validated['field_35a_yes_no'] && empty($validated['field_35_administrative_offense_details'])) {
            $conditionalErrors['field_35_administrative_offense_details'] = 'Please provide details for question 35a.';
        }

        if ($validated['field_35b_yes_no'] && empty($validated['field_36_criminal_charge_details'])) {
            $conditionalErrors['field_36_criminal_charge_details'] = 'Please provide details for question 35b.';
        }

        if ($validated['field_36_yes_no'] && empty($validated['field_36_conviction_details'])) {
            $conditionalErrors['field_36_conviction_details'] = 'Please provide details for question 36.';
        }

        if ($validated['field_37_yes_no'] && empty($validated['field_37_separation_details'])) {
            $conditionalErrors['field_37_separation_details'] = 'Please provide details for question 37.';
        }

        if ($validated['field_38a_yes_no'] && empty($validated['field_36_candidate_details'])) {
            $conditionalErrors['field_36_candidate_details'] = 'Please provide details for question 38a.';
        }

        if ($validated['field_38b_yes_no'] && empty($validated['field_37_resignation_details'])) {
            $conditionalErrors['field_37_resignation_details'] = 'Please provide details for question 38b.';
        }

        if ($validated['field_39_yes_no'] && empty($validated['field_39_immigrant_details'])) {
            $conditionalErrors['field_39_immigrant_details'] = 'Please provide details for question 39.';
        }

        if ($validated['field_40a_yes_no'] && empty($validated['field_40_indigenous_details'])) {
            $conditionalErrors['field_40_indigenous_details'] = 'Please specify your indigenous group for question 40a.';
        }

        if ($validated['field_40b_yes_no'] && empty($validated['field_40_pwd_details'])) {
            $conditionalErrors['field_40_pwd_details'] = 'Please provide your PWD ID number for question 40b.';
        }

        if ($validated['field_40c_yes_no'] && empty($validated['field_40_solo_parent_details'])) {
            $conditionalErrors['field_40_solo_parent_details'] = 'Please provide your Solo Parent ID number for question 40c.';
        }

        \Log::info('PDS updateQuestionnaire - Conditional validation completed', [
            'employee_id' => $employee->id,
            'conditional_errors_count' => count($conditionalErrors),
            'has_errors' => !empty($conditionalErrors),
            'timestamp' => now()->toDateTimeString()
        ]);

        // Use database transaction for data integrity
        return DB::transaction(function () use ($employee, $validated, $conditionalErrors) {

            // Add default values for JSON fields to maintain backward compatibility
            $validated['questions_answers'] = $validated['questions_answers'] ?? [];
            $validated['question_details'] = $validated['question_details'] ?? [];

            \Log::info('PDS updateQuestionnaire - Starting database save', [
                'employee_id' => $employee->id,
                'data_fields_count' => count($validated),
                'timestamp' => now()->toDateTimeString()
            ]);

            // Use updateOrCreate to handle both new and existing records gracefully
            $questionnaire = $employee->questionnaire()->updateOrCreate(
                ['employee_id' => $employee->id],
                $validated
            );

            // Log the database operation
            $queries = DB::getQueryLog();
            \Log::info('PDS updateQuestionnaire - Database queries executed', [
                'employee_id' => $employee->id,
                'query_count' => count($queries),
                'queries' => $queries,
                'questionnaire_id' => $questionnaire->id,
                'is_new_record' => $questionnaire->wasRecentlyCreated,
                'timestamp' => now()->toDateTimeString()
            ]);

            // Calculate completion percentage to determine next action
            $completionPercentage = $questionnaire ? $questionnaire->getCompletionPercentage() : 0;

            \Log::info('PDS updateQuestionnaire - Completion calculation', [
                'employee_id' => $employee->id,
                'questionnaire_id' => $questionnaire->id,
                'completion_percentage' => $completionPercentage,
                'is_complete' => $completionPercentage >= 100,
                'timestamp' => now()->toDateTimeString()
            ]);

            // If there are conditional validation errors, return with warnings but data is saved
            if (!empty($conditionalErrors)) {
                \Log::warning('PDS updateQuestionnaire - Returning with conditional errors', [
                    'employee_id' => $employee->id,
                    'questionnaire_id' => $questionnaire->id,
                    'errors_count' => count($conditionalErrors),
                    'errors' => $conditionalErrors,
                    'timestamp' => now()->toDateTimeString()
                ]);

                return redirect()->back()
                    ->withInput()
                    ->withErrors($conditionalErrors)
                    ->with('warning', 'Your basic answers have been saved, but please provide details for questions marked as "YES" to complete the declaration.');
            }

            // If questionnaire is not complete, stay on the page with a success message
            if ($completionPercentage < 100) {
                \Log::info('PDS updateQuestionnaire - Redirecting back to questionnaire (incomplete)', [
                    'employee_id' => $employee->id,
                    'completion_percentage' => $completionPercentage,
                    'timestamp' => now()->toDateTimeString()
                ]);

                return redirect()->route('pds.questionnaire', $employee)
                    ->with('success', sprintf('Questionnaire saved successfully! Completion: %d%%. Please answer all required questions to complete this section.', round($completionPercentage)));
            }

            // If questionnaire is complete, redirect to dashboard with success message
            \Log::info('PDS updateQuestionnaire - Redirecting to dashboard (complete)', [
                'employee_id' => $employee->id,
                'questionnaire_id' => $questionnaire->id,
                'timestamp' => now()->toDateTimeString()
            ]);

            return redirect()->route('pds.dashboard', $employee)
                ->with('success', 'Questionnaire completed successfully! All required declarations have been saved.');

        }); // End transaction

    } catch (\Illuminate\Validation\ValidationException $e) {
        \Log::error('PDS updateQuestionnaire - Validation failed', [
            'employee_id' => $employee->id,
            'validation_errors' => $e->errors(),
            'timestamp' => now()->toDateTimeString()
        ]);
        throw $e; // Re-throw validation exception

    } catch (\Exception $e) {
        \Log::error('PDS updateQuestionnaire - Unexpected error', [
            'employee_id' => $employee->id,
            'error_message' => $e->getMessage(),
            'error_trace' => $e->getTraceAsString(),
            'timestamp' => now()->toDateTimeString()
        ]);

        return redirect()->back()
            ->withInput()
            ->with('error', 'An error occurred while saving your questionnaire. Please try again or contact support if the problem persists.');
    } finally {
        // Always disable query logging and clean up
        DB::disableQueryLog();
    }
}

    // Photo Management Methods
    public function photo(Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'view');

        return view('pds.photo', compact('employee'));
    }

    public function uploadPhoto(Request $request, Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'update');

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg|max:2048|dimensions:min_width=300,min_height=400',
        ], [
            'photo.dimensions' => 'Photo must be at least 300x400 pixels (3.5cm x 4.5cm at 300 DPI).',
            'photo.max' => 'Photo size must not exceed 2MB.',
            'photo.mimes' => 'Photo must be in JPEG or JPG format.',
        ]);

        try {
            // Deactivate existing photos
            $employee->photos()->update(['is_active' => false]);

            // Create new photo record
            $photo = new EmployeePhoto();
            $photo->employee_id = $employee->id;

            // Process and store photo
            $image = $request->file('photo');
            $photo->photo_size = $image->getSize();
            $photo->photo_format = $image->getClientOriginalExtension();
            $photo->photo_taken_date = now();

            // Store in storage/app/public/employee_photos
            $photoPath = $image->store('employee_photos', 'public');
            $photo->photo_path = $photoPath;
            $photo->is_active = true;

            $photo->save();

            return redirect()->route('pds.photo', $employee)
                ->with('success', 'Identification photo uploaded successfully.');

        } catch (\Exception $e) {
            return redirect()->route('pds.photo', $employee)
                ->with('error', 'Failed to upload photo: ' . $e->getMessage());
        }
    }

    public function uploadThumbmark(Request $request, Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'update');

        $request->validate([
            'thumbmark' => 'required|image|mimes:jpeg,jpg|max:1024|dimensions:min_width=200,min_height=200',
        ], [
            'thumbmark.dimensions' => 'Thumbmark must be at least 200x200 pixels (2.5cm x 2.5cm at 300 DPI).',
            'thumbmark.max' => 'Thumbmark size must not exceed 1MB.',
            'thumbmark.mimes' => 'Thumbmark must be in JPEG or JPG format.',
        ]);

        try {
            // Get or create active photo record
            $photo = $employee->activePhoto ?: new EmployeePhoto();
            $photo->employee_id = $employee->id;
            $photo->is_active = true;

            // Process and store thumbmark
            $image = $request->file('thumbmark');
            $photo->thumbmark_size = $image->getSize();
            $photo->thumbmark_format = $image->getClientOriginalExtension();
            $photo->thumbmark_taken_date = now();

            // Store in storage/app/public/employee_photos
            $thumbmarkPath = $image->store('employee_photos', 'public');
            $photo->thumbmark_path = $thumbmarkPath;

            $photo->save();

            return redirect()->route('pds.photo', $employee)
                ->with('success', 'Thumbmark uploaded successfully.');

        } catch (\Exception $e) {
            return redirect()->route('pds.photo', $employee)
                ->with('error', 'Failed to upload thumbmark: ' . $e->getMessage());
        }
    }

  
    /**
     * Validate entire PDS for CSC compliance
     */
    public function validatePDSForCSC(Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'view');

        $cscValidator = new CSCFormValidationService();
        $completenessCheck = $cscValidator->checkPdsCompleteness($employee);

        return response()->json([
            'is_csc_compliant' => $completenessCheck['is_csc_compliant'],
            'completeness_percentage' => $completenessCheck['completeness_percentage'],
            'missing_required_fields' => $completenessCheck['missing_required_fields'],
            'warnings' => $completenessCheck['warnings'],
            'errors' => $completenessCheck['errors'],
            'validation_details' => $completenessCheck['validation_details'],
        ]);
    }

    /**
     * Get CSC compliance status for dashboard
     */
    public function getCSCComplianceStatus(Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'view');

        $cscValidator = new CSCFormValidationService();
        $completenessCheck = $cscValidator->checkPdsCompleteness($employee);

        return view('partials.csc-compliance-status', [
            'employee' => $employee,
            'completenessCheck' => $completenessCheck,
        ]);
    }

  }
