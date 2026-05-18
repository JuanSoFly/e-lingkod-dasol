<?php

namespace App\Exports;

use App\Exports\Traits\WithExcelFormatting;
use App\Models\Employee;
use App\Services\FilipinoCharacterService;
use App\Services\PDSDataOptimizationService;
use App\Services\PDSDataSanitizationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class PDSExport implements FromView, WithColumnWidths, WithStyles
{
    use WithExcelFormatting;

    protected $employeeIds;
    protected $user;
    protected $includeMetadata;
    protected $filipinoService;
    protected $optimizationService;
    protected $sanitizationService;

    public function __construct(
        array $employeeIds = [],
        $user = null,
        bool $includeMetadata = true
    ) {
        $this->employeeIds = $employeeIds;
        $this->user = $user;
        $this->includeMetadata = $includeMetadata;
        $this->filipinoService = new FilipinoCharacterService();
        $this->optimizationService = new PDSDataOptimizationService();
        $this->sanitizationService = new PDSDataSanitizationService();
    }

    /**
     * Return the view for rendering the multi-panel vertical PDS form
     */
    public function view(): View
    {
        $query = Employee::with([
            'familyBackground',
            'children',
            'education',
            'pdsEligibilities',
            'workExperiences',
            'voluntaryWork',
            'employeeTrainings',
            'otherInformation',
            'references',
            'questionnaire',
            'documents'
        ]);

        if (!empty($this->employeeIds)) {
            $query->whereIn('id', $this->employeeIds);
        }

        $employees = $query->get();
        $employeesData = [];

        $sanitize = fn($value) => $this->filipinoService->cleanForExcel($value ?? '');

        foreach ($employees as $employee) {
            // Sanitize data based on user role
            $employee = $this->sanitizationService->sanitizeForRole($employee, $this->user);

            // Family background
            $family = $employee->familyBackground ?? (object) [];

            // Children
            $children = $employee->children ? $employee->children->map(function ($child) use ($sanitize) {
                return [
                    'name' => $sanitize($child->full_name ?? ($child->first_name ?? '')),
                    'birth_date' => $child->date_of_birth ? Carbon::parse($child->date_of_birth)->format('m/d/Y') : '',
                ];
            })->values()->all() : [];

            // Pad children to at least 12 slots to match official CSC Form structure
            while (count($children) < 12) {
                $children[] = ['name' => '', 'birth_date' => ''];
            }

            // Education
            $educationLabels = [
                'Elementary' => 'ELEMENTARY',
                'Secondary' => 'SECONDARY',
                'Vocational' => 'VOCATIONAL / TRADE COURSE',
                'College' => 'COLLEGE',
                'Graduate Studies' => 'GRADUATE STUDIES',
            ];
            $education = collect(['Elementary', 'Secondary', 'Vocational', 'College', 'Graduate Studies'])->map(function ($level) use ($employee, $sanitize, $educationLabels) {
                $record = $employee->education ? $employee->education->where('education_level', $level)->first() : null;
                return [
                    'level' => $educationLabels[$level] ?? strtoupper($level),
                    'school_name' => $sanitize($record->school_name ?? ''),
                    'degree_course' => $sanitize($record->degree_course ?? $record->degree ?? ''),
                    'period_from' => $record?->period_from ? Carbon::parse($record->period_from)->format('Y') : ($record?->start_date ? Carbon::parse($record->start_date)->format('Y') : ''),
                    'period_to' => $record?->period_to ? Carbon::parse($record->period_to)->format('Y') : ($record?->end_date ? Carbon::parse($record->end_date)->format('Y') : ''),
                    'year_graduated' => $record->year_graduated ?? '',
                    'highest_level' => $sanitize($record->highest_level ?? ''),
                    'honors' => $sanitize($record->scholarship_honors ?? ''),
                ];
            })->all();

            // Civil Service Eligibilities
            $eligibilities = $employee->pdsEligibilities ? $employee->pdsEligibilities->map(function ($eligibility) use ($sanitize) {
                return [
                    'eligibility_name' => $sanitize($eligibility->eligibility_name ?? $eligibility->career_service ?? ''),
                    'rating' => $eligibility->rating ?? '',
                    'exam_date' => $eligibility->date_of_examination ? Carbon::parse($eligibility->date_of_examination)->format('m/d/Y') : ($eligibility->date_acquired ? Carbon::parse($eligibility->date_acquired)->format('m/d/Y') : ''),
                    'exam_place' => $sanitize($eligibility->place_of_examination ?? $eligibility->examination_place ?? ''),
                    'license_number' => $eligibility->license_number ?? '',
                    'validity_date' => $eligibility->date_of_validity ? Carbon::parse($eligibility->date_of_validity)->format('m/d/Y') : '',
                ];
            })->values()->all() : [];

            // Work Experiences
            $workExperiences = $employee->workExperiences ? $employee->workExperiences
                ->sortByDesc('inclusive_date_from')
                ->map(function ($work) use ($sanitize) {
                    return [
                        'from' => $work->inclusive_date_from ? Carbon::parse($work->inclusive_date_from)->format('m/d/Y') : ($work->from_date ? Carbon::parse($work->from_date)->format('m/d/Y') : ''),
                        'to' => $work->inclusive_date_to ? Carbon::parse($work->inclusive_date_to)->format('m/d/Y') : ($work->to_date ? Carbon::parse($work->to_date)->format('m/d/Y') : ''),
                        'position' => $sanitize($work->position_title ?? $work->position ?? ''),
                        'department' => $sanitize($work->department_agency_office ?? $work->company ?? ''),
                        'monthly_salary' => $work->monthly_salary ?? $work->salary ?? '',
                        'salary_grade' => $work->salary_grade_step ?? '',
                        'appointment_status' => $sanitize($work->status_of_appointment ?? $work->status ?? ''),
                        'is_government_service' => $work->is_government_service ? 'Yes' : 'No',
                    ];
                })->values()->all() : [];

            // Voluntary Work
            $voluntaryWork = $employee->voluntaryWork ? $employee->voluntaryWork->map(function ($vol) use ($sanitize) {
                return [
                    'organization' => $sanitize($vol->organization_name_address ?? ''),
                    'from' => $vol->inclusive_date_from ? Carbon::parse($vol->inclusive_date_from)->format('m/d/Y') : '',
                    'to' => $vol->inclusive_date_to ? Carbon::parse($vol->inclusive_date_to)->format('m/d/Y') : '',
                    'hours' => $vol->number_hours ?? '',
                    'position' => $sanitize($vol->position_nature_of_work ?? ''),
                ];
            })->values()->all() : [];

            // Trainings
            $trainings = $employee->employeeTrainings ? $employee->employeeTrainings
                ->sortByDesc('inclusive_date_from')
                ->map(function ($training) use ($sanitize) {
                    return [
                        'title' => $sanitize($training->pds_training_title ?? $training->training_title ?? $training->trainingProgram?->name ?? ''),
                        'from' => $training->pds_inclusive_date_from ? Carbon::parse($training->pds_inclusive_date_from)->format('m/d/Y') : ($training->inclusive_date_from ? Carbon::parse($training->inclusive_date_from)->format('m/d/Y') : ($training->start_date ? Carbon::parse($training->start_date)->format('m/d/Y') : '')),
                        'to' => $training->pds_inclusive_date_to ? Carbon::parse($training->pds_inclusive_date_to)->format('m/d/Y') : ($training->inclusive_date_to ? Carbon::parse($training->inclusive_date_to)->format('m/d/Y') : ($training->end_date ? Carbon::parse($training->end_date)->format('m/d/Y') : '')),
                        'hours' => $training->pds_number_of_hours ?? $training->hours_attended ?? $training->number_of_hours ?? '',
                        'type' => $sanitize($training->pds_type_of_ld ?? $training->delivery_mode ?? ''),
                        'conducted_by' => $sanitize($training->pds_conducted_sponsored_by ?? $training->trainer_name ?? ''),
                    ];
                })->values()->all() : [];

            // Other Information
            $otherInfoObj = $employee->otherInformation ?? collect();
            $otherInfo = [
                'skills' => $sanitize(optional($otherInfoObj->where('information_type', 'special_skills')->first())->description ?? ''),
                'distinctions' => $sanitize(optional($otherInfoObj->where('information_type', 'distinctions')->first())->description ?? ''),
                'memberships' => $sanitize(optional($otherInfoObj->where('information_type', 'memberships')->first())->description ?? ''),
            ];

            // References
            $referencesData = $employee->references ? $employee->references->map(function ($reference) use ($sanitize) {
                return [
                    'name' => $sanitize($reference->full_name ?? ''),
                    'address' => $sanitize($reference->address ?? ''),
                    'telephone' => $reference->telephone_no ?? '',
                ];
            })->values()->all() : [];

            // Questionnaire
            $questionnaireObj = $employee->questionnaire ?? (object) [];
            $q = fn($field) => $questionnaireObj->{$field} ?? '';
            $questionnaire = [
                'q34_3rd_degree' => $q('field_34_yes_no'),
                'q34b_4th_degree' => $q('field_34b_yes_no'),
                'q35a_administrative_offense' => $q('field_35a_yes_no'),
                'q35b_criminal_charge' => $q('field_35b_yes_no'),
                'q36_conviction' => $q('field_36_yes_no'),
                'q37_separation' => $q('field_37_yes_no'),
                'q38a_election_candidacy' => $q('field_38a_yes_no'),
                'q38b_resignation_campaign' => $q('field_38b_yes_no'),
                'q39_immigrant_status' => $q('field_39_yes_no'),
                'q40a_indigenous_group' => $q('field_40a_yes_no'),
                'q40b_person_with_disability' => $q('field_40b_yes_no'),
                'q40c_solo_parent' => $q('field_40c_yes_no'),
                'details' => $questionnaireObj->question_details ?? [],
            ];

            $res = [
                'house_block_lot' => $sanitize($employee->res_house_block_lot_no ?? ''),
                'street' => $sanitize($employee->res_street ?? ''),
                'subdivision' => $sanitize($employee->res_subdivision_village ?? ''),
                'barangay' => $sanitize($employee->res_barangay ?? ''),
                'city_municipality' => $sanitize($employee->res_city_municipality ?? ''),
                'province' => $sanitize($employee->res_province ?? ''),
                'zip_code' => $sanitize($employee->res_zip_code ?? $employee->residential_zip_code ?? ''),
            ];

            $perm = [
                'house_block_lot' => $sanitize($employee->perm_house_block_lot_no ?? ''),
                'street' => $sanitize($employee->perm_street ?? ''),
                'subdivision' => $sanitize($employee->perm_subdivision_village ?? ''),
                'barangay' => $sanitize($employee->perm_barangay ?? ''),
                'city_municipality' => $sanitize($employee->perm_city_municipality ?? ''),
                'province' => $sanitize($employee->perm_province ?? ''),
                'zip_code' => $sanitize($employee->perm_zip_code ?? $employee->permanent_zip_code ?? ''),
            ];

            $employeesData[] = compact(
                'employee', 'family', 'children', 'education', 'eligibilities',
                'workExperiences', 'voluntaryWork', 'trainings', 'otherInfo',
                'referencesData', 'questionnaire', 'res', 'perm'
            );
        }

        return view('pds.export.excel', compact('employeesData'));
    }

    /**
     * Specify column widths for the vertical layout form layout
     */
    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 25,
            'C' => 20,
            'D' => 20,
            'E' => 25,
            'F' => 25,
            'G' => 20,
            'H' => 20,
        ];
    }

    /**
     * Styles for the exported worksheet
     */
    public function styles(Worksheet $sheet)
    {
        // View inline styles handle the table borders, fill colors, and alignment perfectly
        return [];
    }
}