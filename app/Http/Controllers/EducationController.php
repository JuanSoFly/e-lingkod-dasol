<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeEducation;
use App\Models\EmployeeDocument;
use App\Services\AuditTrailService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EducationController extends Controller
{
    use AuthorizesRequests;

    private AuditTrailService $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    /**
     * Display education records for an employee
     */
    public function index(Employee $employee): View
    {
        $this->authorize('view', $employee);
        
        $educations = $employee->education()
            ->orderBy('education_level')
            ->orderBy('period_from', 'desc')
            ->get();

        return view('education.index', compact('employee', 'educations'));
    }

    /**
     * Show the form for creating a new education record
     */
    public function create(Employee $employee): View
    {
        $this->authorize('update', $employee);
        
        return view('education.create', compact('employee'));
    }

    /**
     * Store a newly created education record
     */
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $validated = $this->validateEducation($request);
        $validated['employee_id'] = $employee->id;

        DB::transaction(function () use ($validated, $employee, $request) {
            // Set legacy fields to maintain compatibility
            if (!empty($validated['year_graduated_pds'])) {
                $validated['year_graduated'] = (string) $validated['year_graduated_pds'];
            } elseif (!empty($validated['period_to'])) {
                $validated['year_graduated'] = (string) $validated['period_to'];
            }
            
            // Set legacy course field from degree_course if available
            if (!empty($validated['degree_course'])) {
                $validated['course'] = $validated['degree_course'];
            } elseif (empty($validated['course'])) {
                $validated['course'] = $validated['education_level'] ?? 'Not specified';
            }
            
            $education = EmployeeEducation::create($validated);
            
            // Handle file upload if provided
            if ($request->hasFile('attachment')) {
                $this->handleFileUpload($request, $education, $employee);
            }
        });

        return redirect()
            ->route('employees.education.index', $employee)
            ->with('success', 'Education record added successfully.');
    }

    /**
     * Show the form for editing the specified education record
     */
    public function edit(Employee $employee, EmployeeEducation $education): View
    {
        $this->authorize('update', $employee);
        
        // Ensure the education belongs to this employee
        if ($education->employee_id !== $employee->id) {
            abort(404);
        }

        return view('education.edit', compact('employee', 'education'));
    }

    /**
     * Update the specified education record
     */
    public function update(Request $request, Employee $employee, EmployeeEducation $education): RedirectResponse
    {
        $this->authorize('update', $employee);
        
        // Ensure the education belongs to this employee
        if ($education->employee_id !== $employee->id) {
            abort(404);
        }

        $validated = $this->validateEducation($request);

        // Capture old values before update
        $oldValues = $education->getAttributes();

        DB::transaction(function () use ($validated, $education, $request, $employee, &$oldValues) {
            // Set legacy fields to maintain compatibility
            if (!empty($validated['year_graduated_pds'])) {
                $validated['year_graduated'] = (string) $validated['year_graduated_pds'];
            } elseif (!empty($validated['period_to'])) {
                $validated['year_graduated'] = (string) $validated['period_to'];
            }

            // Set legacy course field from degree_course if available
            if (!empty($validated['degree_course'])) {
                $validated['course'] = $validated['degree_course'];
            } elseif (empty($validated['course'])) {
                $validated['course'] = $validated['education_level'] ?? 'Not specified';
            }

            // Get the final values that will be updated
            $newValues = array_intersect_key($validated, $oldValues);

            $education->update($validated);

            // Handle file upload if provided
            if ($request->hasFile('attachment')) {
                $this->handleFileUpload($request, $education, $employee);
                $newValues['attachment_updated'] = true;
            }

            // Log the education record update
            $this->auditTrailService->logEducationUpdate($education, $oldValues, $newValues);
        });

        return redirect()
            ->route('employees.education.index', $employee)
            ->with('success', 'Education record updated successfully.');
    }

    /**
     * Remove the specified education record
     */
    public function destroy(Employee $employee, EmployeeEducation $education): RedirectResponse
    {
        $this->authorize('update', $employee);
        
        // Ensure the education belongs to this employee
        if ($education->employee_id !== $employee->id) {
            abort(404);
        }

        $education->delete();

        return redirect()
            ->route('employees.education.index', $employee)
            ->with('success', 'Education record deleted successfully.');
    }

    /**
     * Validate education form data
     */
    private function validateEducation(Request $request): array
    {
        $rules = [
            'education_level' => 'required|in:' . implode(',', array_keys(EmployeeEducation::EDUCATION_LEVELS)),
            'school_name' => 'required|string|max:255',
            'course' => 'nullable|string|max:255',
            'degree_course' => 'nullable|string|max:255',
            'period_from' => 'nullable|integer|min:1900|max:' . (date('Y') + 10),
            'period_to' => 'nullable|integer|min:1900|max:' . (date('Y') + 10) . '|gte:period_from',
            'highest_level_units_earned' => 'nullable|string|max:255',
            'year_graduated_pds' => 'nullable|integer|min:1900|max:' . (date('Y') + 10),
            'scholarship_honors_received' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ];

        // Make degree_course required for certain education levels
        $educationLevel = $request->input('education_level');
        if (in_array($educationLevel, ['College', 'Graduate Studies', 'Vocational/Trade'])) {
            $rules['degree_course'] = 'required|string|max:255';
        }

        return $request->validate($rules);
    }

    /**
     * Handle file upload for education documents
     */
    private function handleFileUpload(Request $request, EmployeeEducation $education, Employee $employee): void
    {
        if (!$request->hasFile('attachment')) {
            return;
        }

        $file = $request->file('attachment');
        $originalName = $file->getClientOriginalName();
        
        // Generate unique filename
        $filename = time() . '_' . str_replace(' ', '_', $originalName);
        
        // Store file in employee documents directory
        $path = $file->storeAs(
            "employee_documents/{$employee->id}/education",
            $filename,
            'public'
        );

        // Create employee document record
        $document = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'education_credential',
            'file_name' => $originalName,
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'description' => "Education credential for {$education->education_level} - {$education->school_name}",
            'uploaded_by' => auth()->id(),
        ]);

        // Update education record with attachment reference
        $education->update(['attachment_id' => $document->id]);
    }

    /**
     * Download education document attachment
     */
    public function downloadAttachment(Employee $employee, EmployeeEducation $education)
    {
        $this->authorize('view', $employee);
        
        // Ensure the education belongs to this employee
        if ($education->employee_id !== $employee->id) {
            abort(404);
        }

        if (!$education->attachment_id) {
            abort(404, 'No attachment found for this education record.');
        }

        $document = EmployeeDocument::find($education->attachment_id);
        
        if (!$document || !Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }
}