<?php

namespace App\Http\Controllers;

use App\Models\CareerProgression;
use App\Models\DocumentRequest;
use App\Models\Employee;
use App\Models\EmployeeChangeRequest;
use App\Models\EmployeeTraining;
use App\Models\GovernmentBenefit;
use App\Models\LeaveApplication;
use App\Models\LeaveCredit;
use App\Models\PerformancePeriod;
use App\Models\PerformanceTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeeSelfServiceController extends Controller
{
    /**
     * Display the employee self-service dashboard
     */
    public function dashboard()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee profile not found.');
        }

        // Personal metrics and notifications
        $metrics = $this->getPersonalMetrics($employee);
        $notifications = $this->getPersonalNotifications($employee);
        $announcements = $this->getPersonalAnnouncements($employee);

        return view('employee-portal.dashboard-old', compact('employee', 'metrics', 'notifications', 'announcements'));
    }

    /**
     * Display service record with complete history
     */
    public function serviceRecord()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee profile not found.');
        }

        // Get complete service history
        $serviceHistory = $this->getServiceHistory($employee);
        $careerProgression = $this->getCareerProgressionHistory($employee);
        $trainingHistory = $this->getTrainingHistory($employee);
        $performanceHistory = $this->getPerformanceHistory($employee);
        $educationHistory = $this->getEducationHistory($employee);
        $workExperience = $this->getWorkExperienceHistory($employee);

        return view('employee-portal.service-record', compact(
            'employee', 
            'serviceHistory', 
            'careerProgression', 
            'trainingHistory', 
            'performanceHistory',
            'educationHistory',
            'workExperience'
        ));
    }

    /**
     * Display document request system
     */
    public function documentRequests()
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee profile not found.');
        }

        $documentRequests = $employee->documentRequests()
            ->with(['requestedBy', 'processedBy', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $availableDocuments = DocumentRequest::getDocumentTypes();
        $deliveryMethods = DocumentRequest::getDeliveryMethods();

        return view('employee-portal.document-requests', compact(
            'employee', 
            'documentRequests', 
            'availableDocuments', 
            'deliveryMethods'
        ));
    }

    /**
     * Store a new document request
     */
    public function storeDocumentRequest(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee profile not found.');
        }

        $validated = $request->validate([
            'document_type' => 'required|string|max:100',
            'document_name' => 'required|string|max:255',
            'purpose' => 'required|string|max:1000',
            'description' => 'nullable|string|max:1000',
            'priority' => 'required|in:low,normal,high,urgent',
            'needed_by' => 'nullable|date|after:today',
            'delivery_method' => 'required|in:pickup,email,courier',
            'delivery_address' => 'nullable|string|max:500',
            'delivery_contact' => 'nullable|string|max:100',
        ]);

        $validated['employee_id'] = $employee->id;
        $validated['requested_by_user_id'] = $user->id;

        $documentRequest = DocumentRequest::create($validated);
        $documentRequest->addAuditEntry('created', 'Document request submitted');

        return redirect()->route('employee-portal.document-requests')
            ->with('success', 'Document request submitted successfully.');
    }

    /**
     * Display personal data update system
     */
    public function personalDataUpdate()
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee profile not found.');
        }

        $changeRequests = $employee->changeRequests()
            ->with(['requestedBy', 'reviewedBy', 'implementedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $changeTypes = EmployeeChangeRequest::getChangeTypes();
        $editableFields = EmployeeChangeRequest::getEditableFields();

        return view('employee-portal.personal-data-update', compact(
            'employee', 
            'changeRequests', 
            'changeTypes', 
            'editableFields'
        ));
    }

    /**
     * Store a new data change request
     */
    public function storeChangeRequest(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee profile not found.');
        }

        $validated = $request->validate([
            'change_type' => 'required|string|max:100',
            'field_name' => 'required|string|max:100',
            'requested_value' => 'required|string|max:1000',
            'justification' => 'required|string|max:1000',
            'priority' => 'required|in:low,normal,high,urgent',
            'effective_date' => 'nullable|date|after_or_equal:today',
            'document_notes' => 'nullable|string|max:500',
            'supporting_documents.*' => 'nullable|file|max:5120', // 5MB max per file
        ]);

        // Get current value
        $currentValue = $employee->{$validated['field_name']} ?? '';
        $validated['current_value'] = is_array($currentValue) ? json_encode($currentValue) : (string)$currentValue;
        $validated['employee_id'] = $employee->id;
        $validated['requested_by_user_id'] = $user->id;

        // Handle file uploads
        $uploadedFiles = [];
        if ($request->hasFile('supporting_documents')) {
            foreach ($request->file('supporting_documents') as $file) {
                $path = $file->store('employee_change_requests/' . $employee->id, 's3');
                $uploadedFiles[] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'uploaded_at' => now()->toISOString(),
                ];
            }
        }
        $validated['supporting_documents'] = $uploadedFiles;

        $changeRequest = EmployeeChangeRequest::create($validated);
        $changeRequest->addAuditEntry('created', 'Change request submitted');

        return redirect()->route('employee-portal.personal-data-update')
            ->with('success', 'Change request submitted successfully.');
    }

    /**
     * Display benefits summary
     */
    public function benefitsSummary()
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee profile not found.');
        }

        // Government benefits overview
        $benefitsSummary = $employee->getGovernmentBenefitsSummary();
        $contributionsSummary = $employee->getTotalContributionsForYear(now()->year);
        $benefitsCompliance = $employee->hasRequiredBenefitsEnrolled();
        $overdueContributions = $employee->getOverdueContributionsSummary();
        $activeLoansSummary = $employee->getActiveLoansSummary();

        // Leave balances and utilization
        $leaveBalances = $this->getLeaveBalances($employee);
        $leaveUtilization = $this->getLeaveUtilization($employee);

        return view('employee-portal.benefits-summary', compact(
            'employee',
            'benefitsSummary',
            'contributionsSummary',
            'benefitsCompliance',
            'overdueContributions',
            'activeLoansSummary',
            'leaveBalances',
            'leaveUtilization'
        ));
    }

    /**
     * Download document request file
     */
    public function downloadDocumentRequest(DocumentRequest $documentRequest)
    {
        $user = Auth::user();
        
        // Ensure user can only download their own requests
        $employee = $user->employee;
        if (!$employee || $documentRequest->employee_id !== $employee->id) {
            abort(403, 'Unauthorized access to document.');
        }

        if (!$documentRequest->hasFile()) {
            return redirect()->back()->with('error', 'Document file not available.');
        }

        if ($documentRequest->isExpired()) {
            return redirect()->back()->with('error', 'Document has expired and is no longer available.');
        }

        $filePath = storage_path('app/private/' . $documentRequest->generated_file_path);
        
        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'Document file not found.');
        }

        return response()->download($filePath, $documentRequest->generated_file_name);
    }

    /**
     * Get personal metrics for dashboard
     */
    private function getPersonalMetrics(Employee $employee): array
    {
        $currentYear = now()->year;
        $latestEvaluation = $employee->performanceEvaluations()
            ->where('evaluation_status', 'final')
            ->orderByDesc('evaluation_date')
            ->orderByDesc('created_at')
            ->first();

        $latestRating = $latestEvaluation?->overall_rating ?? $employee->latest_performance_rating;

        $activePeriod = PerformancePeriod::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->orderByDesc('start_date')
            ->first();

        $targetsBaseQuery = $employee->performanceTargets()
            ->when($activePeriod, function ($query) use ($activePeriod) {
                $query->where('period_id', $activePeriod->id);
            }, function ($query) use ($currentYear) {
                $query->whereYear('created_at', $currentYear);
            });

        $targetsThisPeriod = (clone $targetsBaseQuery)->count();

        $completedTargets = (clone $targetsBaseQuery)
            ->where(function ($query) {
                $query->where('is_target_met', true)
                    ->orWhereHas('rating', function ($ratingQuery) {
                        $ratingQuery->whereNotNull('final_rating')
                            ->orWhereNotNull('average_qet_rating')
                            ->orWhere(function ($subQuery) {
                                $subQuery->whereNotNull('self_rating')
                                    ->whereNotNull('supervisor_rating');
                            });
                    });
            })
            ->count();
        
        return [
            'leave_balance' => [
                'total' => $employee->leaveCredits()->sum('remaining_credits'),
                'used_this_year' => $employee->leaveApplications()
                    ->whereYear('start_date', $currentYear)
                    ->where('status', 'approved')
                    ->sum('days_requested'),
                'pending_applications' => $employee->leaveApplications()
                    ->where('status', 'pending')
                    ->count(),
            ],
            'document_requests' => [
                'pending' => $employee->documentRequests()->pending()->count(),
                'ready' => $employee->documentRequests()->ready()->count(),
                'total_this_year' => $employee->documentRequests()
                    ->whereYear('created_at', $currentYear)
                    ->count(),
            ],
            'change_requests' => [
                'pending' => $employee->changeRequests()->pending()->count(),
                'under_review' => $employee->changeRequests()->underReview()->count(),
                'approved' => $employee->changeRequests()
                    ->approved()
                    ->whereNull('implemented_at')
                    ->count(),
            ],
            'performance' => [
                'latest_rating' => $latestRating,
                'targets_this_period' => $targetsThisPeriod,
                'completed_targets' => $completedTargets,
            ],
            'training' => [
                'hours_this_year' => 0, // Will implement later when training system is fully set up
                'completed_trainings' => 0,
                'upcoming_trainings' => 0,
            ],
        ];
    }

    /**
     * Get personal notifications
     */
    private function getPersonalNotifications(Employee $employee): array
    {
        $notifications = [];

        // Upcoming deadlines
        $upcomingBirthday = $employee->birth_date && \Carbon\Carbon::parse($employee->birth_date)->format('m-d') === now()->addDays(7)->format('m-d');
        if ($upcomingBirthday) {
            $notifications[] = [
                'type' => 'info',
                'title' => 'Birthday Reminder',
                'message' => 'Your birthday is coming up in a week!',
                'date' => now(),
            ];
        }

        // Overdue document requests
        $overdueRequests = $employee->documentRequests()->overdue()->count();
        if ($overdueRequests > 0) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Overdue Document Requests',
                'message' => "You have {$overdueRequests} document request(s) that are overdue.",
                'date' => now(),
            ];
        }

        // Ready documents
        $readyDocuments = $employee->documentRequests()->ready()->count();
        if ($readyDocuments > 0) {
            $notifications[] = [
                'type' => 'success',
                'title' => 'Documents Ready',
                'message' => "You have {$readyDocuments} document(s) ready for pickup/download.",
                'date' => now(),
            ];
        }

        // Pending change requests
        $pendingChanges = $employee->changeRequests()->pending()->count();
        if ($pendingChanges > 0) {
            $notifications[] = [
                'type' => 'info',
                'title' => 'Pending Change Requests',
                'message' => "You have {$pendingChanges} change request(s) pending review.",
                'date' => now(),
            ];
        }

        return array_slice($notifications, 0, 5); // Limit to 5 notifications
    }

    /**
     * Get personal announcements
     */
    private function getPersonalAnnouncements(Employee $employee): array
    {
        // This would typically come from a database table
        // For now, returning static announcements
        return [
            [
                'title' => 'System Maintenance Notice',
                'message' => 'The HRIS system will undergo maintenance this weekend.',
                'type' => 'warning',
                'date' => now()->subDays(1),
                'is_important' => false,
            ],
            [
                'title' => 'Performance Review Period',
                'message' => 'Annual performance review period has started. Please complete your self-assessments.',
                'type' => 'info',
                'date' => now()->subDays(3),
                'is_important' => true,
            ],
        ];
    }

    /**
     * Get service history timeline
     */
    private function getServiceHistory(Employee $employee): array
    {
        $history = [];

        // Employment start
        $history[] = [
            'date' => $employee->date_hired,
            'event' => 'Employment Started',
            'description' => "Hired as {$employee->position} in {$employee->department}",
            'type' => 'employment',
        ];

        // Add career progressions
        foreach ($employee->careerProgressions as $progression) {
            $history[] = [
                'date' => $progression->effective_date,
                'event' => ucfirst($progression->progression_type),
                'description' => "From {$progression->from_position} to {$progression->to_position}",
                'type' => 'promotion',
            ];
        }

        // Sort by date
        usort($history, function($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        return $history;
    }

    /**
     * Get ordered career progression records
     */
    private function getCareerProgressionHistory(Employee $employee)
    {
        return $employee->careerProgressions()
            ->orderByDesc('effective_date')
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Get employee training records with program details
     */
    private function getTrainingHistory(Employee $employee)
    {
        return $employee->employeeTrainings()
            ->with('trainingProgram')
            ->orderByDesc(DB::raw('COALESCE(end_date, start_date, certificate_date, created_at)'))
            ->get();
    }

    /**
     * Get performance reviews with linked period and targets
     */
    private function getPerformanceHistory(Employee $employee)
    {
        return $employee->performanceReviews()
            ->with(['performancePeriod', 'performanceTargets'])
            ->orderByDesc(DB::raw('COALESCE(review_date, updated_at, created_at)'))
            ->get();
    }

    /**
     * Get education history ordered by level and recency
     */
    private function getEducationHistory(Employee $employee)
    {
        $levelOrder = [
            'Graduate Studies',
            'College',
            'Vocational/Trade',
            'Secondary',
            'Elementary',
        ];

        $orderSql = "FIELD(education_level, '" . implode("','", $levelOrder) . "')";

        return $employee->education()
            ->orderByRaw($orderSql)
            ->orderByDesc(DB::raw('COALESCE(period_to, year_graduated_pds, year_graduated, period_from, created_at)'))
            ->get();
    }

    /**
     * Get work experience timeline entries
     */
    private function getWorkExperienceHistory(Employee $employee)
    {
        return $employee->workExperiences()
            ->orderByDesc(DB::raw('COALESCE(inclusive_date_to, to_date, inclusive_date_from, from_date, created_at)'))
            ->get();
    }

    /**
     * Get leave balances
     */
    private function getLeaveBalances(Employee $employee): array
    {
        $leaveBalances = [];
        $leaveCredits = $employee->leaveCredits()->with('leaveType')->get();

        foreach ($leaveCredits as $credit) {
            $leaveBalances[] = [
                'leave_type' => $credit->leaveType->name,
                'earned' => $credit->earned,
                'used' => $credit->used,
                'balance' => $credit->balance,
                'year' => $credit->year,
            ];
        }

        return $leaveBalances;
    }

    /**
     * Get leave utilization for current year
     */
    private function getLeaveUtilization(Employee $employee): array
    {
        $currentYear = now()->year;
        $leaveApplications = $employee->leaveApplications()
            ->with('leaveType')
            ->whereYear('start_date', $currentYear)
            ->where('status', 'approved')
            ->get();

        $utilization = [];
        foreach ($leaveApplications->groupBy('leave_type_id') as $leaveTypeId => $applications) {
            $leaveType = $applications->first()->leaveType;
            $utilization[] = [
                'leave_type' => $leaveType->name,
                'total_days' => $applications->sum('days_requested'),
                'applications_count' => $applications->count(),
            ];
        }

        return $utilization;
    }

    /**
     * Show new document request form
     */
    public function newDocumentRequest()
    {
        $user = Auth::user();
        $employee = $user->employee;
        
        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee profile not found.');
        }

        $availableDocuments = DocumentRequest::getDocumentTypes();
        $deliveryMethods = DocumentRequest::getDeliveryMethods();

        return view('employee-portal.document-requests.new', compact(
            'employee',
            'availableDocuments',
            'deliveryMethods'
        ));
    }

    /**
     * Show new personal data update form
     */
    public function newPersonalDataUpdate()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee profile not found.');
        }

        $changeTypes = EmployeeChangeRequest::getChangeTypes();
        $editableFields = EmployeeChangeRequest::getEditableFields();

        return view('employee-portal.personal-data-update.new', compact(
            'employee',
            'changeTypes',
            'editableFields'
        ));
    }

    /**
     * Display employee's 201 file
     */
    public function my201File()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee profile not found.');
        }

        // Get comprehensive employee data for 201 file
        $educationHistory = $employee->education()->orderBy('period_from', 'desc')->get();
        $workExperience = $employee->workExperiences()->orderBy('from_date', 'desc')->get();
        $documents = $employee->documents()->orderBy('document_type', 'asc')->get();
        $familyBackground = $employee->familyBackground;
        $children = $employee->children()->orderBy('date_of_birth', 'desc')->get();
        $eligibilities = $employee->pdsEligibilities()->orderBy('created_at', 'desc')->get();
        $voluntaryWork = $employee->voluntaryWork()->orderBy('inclusive_date_from', 'desc')->get();
        $trainingPrograms = $employee->employeeTrainings() ? $employee->employeeTrainings()->with('trainingProgram')->orderBy('start_date', 'desc')->get() : collect();
        $otherInformation = $employee->otherInformation;
        $references = $employee->references()->get();
        $questionnaire = $employee->questionnaire;

        // Get office assignments for role display
        $officeAssignments = $user->officeAssignments()
            ->with('office')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            })
            ->orderBy('assigned_date', 'desc')
            ->get();

        return view('employee-portal.my-201-file', compact(
            'employee',
            'educationHistory',
            'workExperience',
            'documents',
            'familyBackground',
            'children',
            'eligibilities',
            'voluntaryWork',
            'trainingPrograms',
            'otherInformation',
            'references',
            'questionnaire',
            'officeAssignments'
        ));
    }
}
