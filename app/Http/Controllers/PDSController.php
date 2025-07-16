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
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'name_extension' => 'nullable|string|max:10',
            'birth_date' => 'required|date',
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
            // Government IDs
            'gsis_number' => 'nullable|string|max:50',
            'pagibig_number' => 'nullable|string|max:50',
            'philhealth_number' => 'nullable|string|max:50',
            'sss_number' => 'nullable|string|max:50',
            'tin_number' => 'nullable|string|max:50',
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
            // Contact Info
            'telephone_no' => 'nullable|string|max:20',
            'mobile_no' => 'nullable|string|max:20',
            'email' => 'required|email|unique:employees,email,' . $employee->id,
        ]);

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
            // Spouse information
            'spouse_surname' => 'nullable|string|max:255',
            'spouse_first_name' => 'nullable|string|max:255',
            'spouse_middle_name' => 'nullable|string|max:255',
            'spouse_occupation' => 'nullable|string|max:255',
            'spouse_employer' => 'nullable|string|max:255',
            'spouse_business_address' => 'nullable|string',
            'spouse_telephone_no' => 'nullable|string|max:20',
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
                ])
            );

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

        $validated = $request->validate([
            'eligibility_name' => 'required|string|max:255',
            'rating' => 'nullable|numeric|min:0|max:100',
            'date_of_examination' => 'nullable|date',
            'place_of_examination' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:255',
            'date_of_validity' => 'nullable|date',
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

        $validated = $request->validate([
            'organization_name_address' => 'required|string',
            'inclusive_date_from' => 'required|date',
            'inclusive_date_to' => 'nullable|date|after_or_equal:inclusive_date_from',
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

        $validated = $request->validate([
            'inclusive_date_from' => 'required|date',
            'inclusive_date_to' => 'nullable|date|after_or_equal:inclusive_date_from',
            'position_title' => 'required|string|max:255',
            'department_agency_office' => 'required|string|max:255',
            'monthly_salary' => 'nullable|numeric|min:0',
            'salary_grade_step' => 'nullable|string|max:50',
            'status_of_appointment' => 'required|in:Permanent,Temporary,Casual,Contractual,Job Order,Contract of Service',
            'is_government_service' => 'required|boolean',
        ]);

        $employee->workExperiences()->create($validated);

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

        $validated = $request->validate([
            'training_title' => 'required|string|max:255',
            'inclusive_date_from' => 'required|date',
            'inclusive_date_to' => 'nullable|date|after_or_equal:inclusive_date_from',
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

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'address' => 'required|string',
            'telephone_no' => 'nullable|string|max:20',
        ]);

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
        
        $questionnaire = $employee->questionnaire;
        $questionLabels = EmployeeQuestionnaire::getQuestionLabels();
        
        return view('pds.questionnaire', compact('employee', 'questionnaire', 'questionLabels'));
    }

    public function updateQuestionnaire(Request $request, Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'update');

        $questionLabels = EmployeeQuestionnaire::getQuestionLabels();
        $rules = [];
        
        foreach (array_keys($questionLabels) as $questionCode) {
            $rules["questions.{$questionCode}"] = 'required|boolean';
            $rules["details.{$questionCode}"] = 'nullable|string';
        }

        $validated = $request->validate($rules);

        $questionnaire = $employee->questionnaire()->firstOrNew(['employee_id' => $employee->id]);
        $questionnaire->questions_answers = $validated['questions'];
        $questionnaire->question_details = array_filter($validated['details'] ?? []);
        $questionnaire->save();

        return redirect()->route('pds.dashboard', $employee)
            ->with('success', 'Questionnaire updated successfully.');
    }

    // PDS PDF Generation
    public function generatePDF(Employee $employee)
    {
        $this->authorizePdsAccess($employee, 'view');
        
        try {
            // Load employee with all related PDS data
            $employee->load([
                'familyBackground',
                'children',
                'education',
                'pdsEligibilities',
                'workExperiences',
                'voluntaryWork',
                'trainings',
                'specialSkills',
                'distinctions',
                'memberships',
                'references',
                'questionnaire'
            ]);

            // Get complete PDS data
            $pdsData = $employee->getPdsDataForPdf();

            // Generate PDF using the CSC Form No. 212 template
            $pdf = Pdf::loadView('pds.pdf.form212', [
                'employee' => $employee,
                'pdsData' => $pdsData
            ]);

            // Set PDF options
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', true);

            // Generate filename with employee name
            $filename = 'PDS_' . str_replace(' ', '_', $employee->full_name) . '_' . date('Y-m-d') . '.pdf';

            // Return PDF for download
            return $pdf->download($filename);

        } catch (\Exception $e) {
            // Log error and return user-friendly message
            logger()->error('PDS PDF generation failed', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Error generating PDF. Please try again or contact support.'
            ], 500);
        }
    }
}