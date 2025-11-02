<?php

namespace App\Exports;

use App\Exports\Traits\WithExcelFormatting;
use App\Models\Employee;
use App\Services\FilipinoCharacterService;
use App\Services\PDSDataOptimizationService;
use App\Services\PDSDataSanitizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class PDSExport implements FromQuery, WithMapping, WithHeadings, WithColumnWidths, WithStyles
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
     * Query for employees to export
     */
    public function query()
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

        return $query;
    }

    /**
     * Map employee data to Excel rows
     */
    public function map($employee): array
    {
        // Sanitize data based on user role
        $employee = $this->sanitizationService->sanitizeForRole($employee, $this->user);

        $data = [];

        // === PANEL 1: PERSONAL INFORMATION ===
        $data = array_merge($data, [
            $employee->employee_number ?? '',
            $this->filipinoService->cleanForExcel($employee->last_name ?? ''),
            $this->filipinoService->cleanForExcel($employee->first_name ?? ''),
            $this->filipinoService->cleanForExcel($employee->middle_name ?? ''),
            $this->filipinoService->cleanForExcel($employee->name_extension ?? ($employee->suffix ?? '')),
            $employee->birth_date ? Carbon::parse($employee->birth_date)->format('m/d/Y') : '',
            $employee->place_of_birth ?? '',
            $employee->gender ?? $employee->sex ?? '',
            $employee->civil_status ?? '',
            $employee->citizenship ?? '',
            $employee->height ?? '',
            $employee->weight ?? '',
            $employee->blood_type ?? '',
            $employee->gsis_number ?? '',
            $employee->pagibig_number ?? '',
            $employee->philhealth_number ?? '',
            $employee->sss_number ?? '',
            $employee->tin_number ?? '',
            $employee->agency_employee_no ?? '',
            $employee->residential_address ?? $this->formatAddress($employee, 'res'),
            $employee->residential_zip_code ?? $employee->res_zip_code ?? '',
            $employee->residential_telephone_no ?? $employee->telephone_no ?? '',
            $employee->permanent_address ?? $this->formatAddress($employee, 'perm'),
            $employee->permanent_zip_code ?? $employee->perm_zip_code ?? '',
            $employee->permanent_telephone_no ?? $employee->telephone_no ?? '',
            $employee->mobile_no ?? $employee->contact_number ?? '',
            $employee->email_address ?? $employee->email ?? '',
        ]);

        // === PANEL 2: FAMILY BACKGROUND ===
        $familyBg = $employee->familyBackground ?? (object) [];
        $familyData = $this->getFamilyBackgroundDataForExport($familyBg);
        $data = array_merge($data, [
            $this->filipinoService->cleanForExcel($familyData['spouse_surname']),
            $this->filipinoService->cleanForExcel($familyData['spouse_first_name']),
            $this->filipinoService->cleanForExcel($familyData['spouse_middle_name']),
            $familyData['spouse_occupation'],
            $familyData['spouse_employer'],
            $familyData['spouse_business_address'],
            $familyData['spouse_telephone_no'],
            $this->filipinoService->cleanForExcel($familyData['father_surname']),
            $this->filipinoService->cleanForExcel($familyData['father_first_name']),
            $this->filipinoService->cleanForExcel($familyData['father_middle_name']),
            '', // Removed fabricated father_extension_name field
            $this->filipinoService->cleanForExcel($familyData['mother_surname']),
            $this->filipinoService->cleanForExcel($familyData['mother_first_name']),
            $this->filipinoService->cleanForExcel($familyData['mother_middle_name']),
        ]);

        // === CHILDREN ===
        $children = $employee->children ? $employee->children->take(5) : collect(); // Limit to 5 children for Excel layout
        for ($i = 0; $i < 5; $i++) {
            if (isset($children[$i])) {
                $child = $children[$i];
                $data = array_merge($data, [
                    $this->filipinoService->cleanForExcel($child->full_name ?? ''),
                    $child->date_of_birth ? Carbon::parse($child->date_of_birth)->format('m/d/Y') : '',
                ]);
            } else {
                $data = array_merge($data, ['', '']);
            }
        }

        // === PANEL 3: EDUCATIONAL BACKGROUND ===
        $education = $employee->education ? $employee->education->sortBy('level') : collect();
        $levels = ['Elementary', 'Secondary', 'College', 'Vocational', 'Graduate Studies'];

        foreach ($levels as $level) {
            $levelEducation = $education->where('education_level', $level)->first();
            if ($levelEducation) {
                $educationData = $this->getEducationDataForExport($levelEducation);
                $data = array_merge($data, [
                    $this->filipinoService->cleanForExcel($educationData['school_name']),
                    $this->filipinoService->cleanForExcel($educationData['degree_course']),
                    $educationData['period_from'],
                    $educationData['period_to'],
                    $educationData['year_graduated'],
                    $educationData['highest_level'],
                    $this->filipinoService->cleanForExcel($educationData['honors']),
                ]);
            } else {
                $data = array_merge($data, ['', '', '', '', '', '', '']);
            }
        }

        // === PANEL 4: CIVIL SERVICE ELIGIBILITY ===
        $eligibilities = $employee->pdsEligibilities ? $employee->pdsEligibilities->take(3) : collect();
        for ($i = 0; $i < 3; $i++) {
            if (isset($eligibilities[$i])) {
                $eligibility = $eligibilities[$i];
                $eligibilityData = $this->getEligibilityDataForExport($eligibility);
                $data = array_merge($data, [
                    $this->filipinoService->cleanForExcel($eligibilityData['eligibility_name']),
                    $eligibilityData['rating'],
                    $eligibilityData['date_of_examination'],
                    $this->filipinoService->cleanForExcel($eligibilityData['place_of_examination']),
                    $eligibilityData['license_number'],
                    $eligibilityData['date_of_validity'],
                ]);
            } else {
                $data = array_merge($data, ['', '', '', '', '', '']);
            }
        }

        // === PANEL 5: WORK EXPERIENCE ===
        $workExperiences = $employee->workExperiences ? $employee->workExperiences->sortByDesc('inclusive_date_from')->take(5) : collect();
        for ($i = 0; $i < 5; $i++) {
            if (isset($workExperiences[$i])) {
                $work = $workExperiences[$i];
                $workData = $this->getWorkExperienceDataForExport($work);
                $data = array_merge($data, [
                    $workData['from_date'],
                    $workData['to_date'],
                    $this->filipinoService->cleanForExcel($workData['position']),
                    $this->filipinoService->cleanForExcel($workData['department']),
                    $workData['monthly_salary'],
                    $workData['salary_grade'],
                    $workData['step_increment'],
                    $workData['appointment_status'],
                    $workData['is_government_service'],
                ]);
            } else {
                $data = array_merge($data, ['', '', '', '', '', '', '', '', '']);
            }
        }

        // === PANEL 6: VOLUNTARY WORK ===
        $voluntaryWork = $employee->voluntaryWork ? $employee->voluntaryWork->take(2) : collect();
        for ($i = 0; $i < 2; $i++) {
            if (isset($voluntaryWork[$i])) {
                $voluntary = $voluntaryWork[$i];
                $data = array_merge($data, [
                    $this->filipinoService->cleanForExcel($voluntary->organization_name_address ?? ''),
                    $voluntary->inclusive_date_from ? Carbon::parse($voluntary->inclusive_date_from)->format('m/d/Y') : '',
                    $voluntary->inclusive_date_to ? Carbon::parse($voluntary->inclusive_date_to)->format('m/d/Y') : '',
                    $voluntary->number_hours ?? '',
                    $this->filipinoService->cleanForExcel($voluntary->position_nature_of_work ?? ''),
                ]);
            } else {
                $data = array_merge($data, ['', '', '', '', '']);
            }
        }

        // === PANEL 7: TRAINING PROGRAMS ===
        $training = $employee->employeeTrainings ? $employee->employeeTrainings->sortByDesc('inclusive_date_from')->take(3) : collect();
        for ($i = 0; $i < 3; $i++) {
            if (isset($training[$i])) {
                $program = $training[$i];
                $trainingData = $this->getTrainingDataForExport($program);
                $data = array_merge($data, [
                    $this->filipinoService->cleanForExcel($trainingData['title']),
                    $trainingData['from_date'],
                    $trainingData['to_date'],
                    $trainingData['hours'],
                    $this->filipinoService->cleanForExcel($trainingData['type']),
                    $this->filipinoService->cleanForExcel($trainingData['conducted_by']),
                ]);
            } else {
                $data = array_merge($data, ['', '', '', '', '', '']);
            }
        }

        // === PANEL 8: OTHER INFORMATION ===
        $otherInfo = $employee->otherInformation ?? collect();

        // Get other information by type
        $specialSkills = $otherInfo->where('information_type', 'special_skills')->first();
        $distinctions = $otherInfo->where('information_type', 'distinctions')->first();
        $memberships = $otherInfo->where('information_type', 'memberships')->first();

        $data = array_merge($data, [
            $this->filipinoService->cleanForExcel($specialSkills->description ?? ''),
            $this->filipinoService->cleanForExcel($distinctions->description ?? ''),
            $this->filipinoService->cleanForExcel($memberships->description ?? ''),
        ]);

        // === PANEL 9: REFERENCES ===
        $references = $employee->references ? $employee->references->take(3) : collect();
        for ($i = 0; $i < 3; $i++) {
            if (isset($references[$i])) {
                $reference = $references[$i];
                $data = array_merge($data, [
                    $this->filipinoService->cleanForExcel($reference->full_name ?? ''),
                    $this->filipinoService->cleanForExcel($reference->address ?? ''),
                    $reference->telephone_no ?? '',
                ]);
            } else {
                $data = array_merge($data, ['', '', '']);
            }
        }

        // === PANEL 10: GOVERNMENT ISSUED IDs ===
        $data = array_merge($data, [
            $employee->gov_id_type ?? '',
            $employee->gov_id_number ?? '',
            $employee->gov_id_date_issued ? Carbon::parse($employee->gov_id_date_issued)->format('m/d/Y') : '',
            $employee->gov_id_place_issued ?? '',
        ]);

        // === QUESTIONNAIRE ===
        $questionnaire = $employee->questionnaire ?? (object) [];
        $questionnaireData = $this->getQuestionnaireDataForExport($questionnaire);
        $data = array_merge($data, [
            $questionnaireData['q34_3rd_degree'],
            $questionnaireData['q34b_4th_degree'],
            $questionnaireData['q35a_administrative_offense'],
            $questionnaireData['q35b_criminal_charge'],
            $questionnaireData['q36_conviction'],
            $questionnaireData['q37_separation'],
            $questionnaireData['q38a_election_candidacy'],
            $questionnaireData['q38b_resignation_campaign'],
            $questionnaireData['q39_immigrant_status'],
            $questionnaireData['q40a_indigenous_group'],
            $questionnaireData['q40b_person_with_disability'],
            $questionnaireData['q40c_solo_parent'],
        ]);

        // === DOCUMENT METADATA ===
        if ($this->includeMetadata) {
            $documents = $employee->documents ?? collect();
            $data = array_merge($data, [
                $documents->count(),
                $documents->where('document_type', 'Personal Data Sheet')->count(),
                $documents->where('document_type', 'Transcript of Records')->count(),
                $documents->where('document_type', 'Diploma')->count(),
                $documents->where('document_type', 'Certificate of Employment')->count(),
                $documents->where('document_type', 'Training Certificates')->count(),
                $documents->where('document_type', 'Eligibility')->count(),
                $documents->sum('file_size') ?? 0,
            ]);
        }

        return $data;
    }

    /**
     * Get training data with intelligent field mapping for export
     */
    private function getTrainingDataForExport($training)
    {
        // Intelligent fallback: PDS fields → Legacy fields → Default
        return [
            'title' => $training->pds_training_title ?? $training->training_title ?? $training->trainingProgram?->name ?? '',
            'from_date' => $training->pds_inclusive_date_from ?? $training->start_date?->format('m/d/Y') ?? '',
            'to_date' => $training->pds_inclusive_date_to ?? $training->end_date?->format('m/d/Y') ?? '',
            'hours' => $training->pds_number_of_hours ?? $training->hours_attended ?? $training->number_of_hours ?? '',
            'type' => $training->pds_type_of_ld ?? $training->delivery_mode ?? '',
            'conducted_by' => $training->pds_conducted_sponsored_by ?? $training->trainer_name ?? '',
        ];
    }

    /**
     * Get education data with intelligent field mapping for export
     */
    private function getEducationDataForExport($education)
    {
        return [
            'school_name' => $education->school_name ?? '',
            'degree_course' => $education->degree_course ?? $education->degree ?? '',
            'period_from' => $education->period_from ? Carbon::parse($education->period_from)->format('m/d/Y') : ($education->start_date?->format('m/d/Y') ?? ''),
            'period_to' => $education->period_to ? Carbon::parse($education->period_to)->format('m/d/Y') : ($education->end_date?->format('m/d/Y') ?? ''),
            'year_graduated' => $education->year_graduated ?? ($education->period_to ? Carbon::parse($education->period_to)->format('Y') : ''),
            'highest_level' => $education->highest_level ?? '',
            'honors' => $education->scholarship_honors ?? '',
        ];
    }

    /**
     * Get work experience data with intelligent field mapping for export
     */
    private function getWorkExperienceDataForExport($work)
    {
        return [
            'from_date' => $work->inclusive_date_from?->format('m/d/Y') ?? $work->from_date?->format('m/d/Y') ?? '',
            'to_date' => $work->inclusive_date_to?->format('m/d/Y') ?? $work->to_date?->format('m/d/Y') ?? '',
            'position' => $work->position_title ?? $work->position ?? '',
            'department' => $work->department_agency_office ?? $work->company ?? '',
            'monthly_salary' => $work->monthly_salary ?? $work->salary ?? '',
            'salary_grade' => $work->salary_grade_step ?? '',
            'step_increment' => '',
            'appointment_status' => $work->status_of_appointment ?? $work->status ?? '',
            'is_government_service' => $work->is_government_service ? 'Yes' : 'No',
        ];
    }

    /**
     * Get questionnaire data with intelligent field mapping for export
     */
    private function getQuestionnaireDataForExport($questionnaire)
    {
        // Check individual fields first, then fallback to JSON
        $getAnswer = function($field, $jsonKey) use ($questionnaire) {
            // Try individual field first
            if ($questionnaire->$field !== null && $questionnaire->$field !== '') {
                return $questionnaire->$field;
            }

            // Fallback to JSON structure
            $jsonAnswers = $questionnaire->questions_answers ?? [];
            return $jsonAnswers[$jsonKey] ?? null;
        };

        return [
            'q34_3rd_degree' => $this->formatQuestionnaireAnswer(
                $getAnswer('field_34_yes_no', 'q34a'),
                $questionnaire->field_34_relationship_details ?? $questionnaire->question_details['q34a'] ?? ''
            ),
            'q34b_4th_degree' => $this->formatQuestionnaireAnswer(
                $getAnswer('field_34b_yes_no', 'q34b'),
                $questionnaire->field_34b_relationship_details ?? $questionnaire->question_details['q34b'] ?? ''
            ),
            'q35a_administrative_offense' => $this->formatQuestionnaireAnswer(
                $getAnswer('field_35a_yes_no', 'q35a'),
                $questionnaire->field_35_administrative_offense_details ?? $questionnaire->question_details['q35a'] ?? ''
            ),
            'q35b_criminal_charge' => $this->formatQuestionnaireAnswer(
                $getAnswer('field_35b_yes_no', 'q35b'),
                $questionnaire->field_36_criminal_charge_details ?? $questionnaire->question_details['q35b'] ?? ''
            ),
            'q36_conviction' => $this->formatQuestionnaireAnswer(
                $getAnswer('field_36_yes_no', 'q36'),
                $questionnaire->field_36_conviction_details ?? $questionnaire->question_details['q36'] ?? ''
            ),
            'q37_separation' => $this->formatQuestionnaireAnswer(
                $getAnswer('field_37_yes_no', 'q37'),
                $questionnaire->field_37_separation_details ?? $questionnaire->question_details['q37'] ?? ''
            ),
            'q38a_election_candidacy' => $this->formatQuestionnaireAnswer(
                $getAnswer('field_38a_yes_no', 'q38a'),
                $questionnaire->field_36_candidate_details ?? $questionnaire->question_details['q38a'] ?? ''
            ),
            'q38b_resignation_campaign' => $this->formatQuestionnaireAnswer(
                $getAnswer('field_38b_yes_no', 'q38b'),
                $questionnaire->field_37_resignation_details ?? $questionnaire->question_details['q38b'] ?? ''
            ),
            'q39_immigrant_status' => $this->formatQuestionnaireAnswer(
                $getAnswer('field_39_yes_no', 'q39'),
                $questionnaire->field_39_immigrant_details ?? $questionnaire->question_details['q39'] ?? ''
            ),
            'q40a_indigenous_group' => $this->formatQuestionnaireAnswer(
                $getAnswer('field_40a_yes_no', 'q40a'),
                $questionnaire->field_40_indigenous_details ?? $questionnaire->question_details['q40a'] ?? ''
            ),
            'q40b_person_with_disability' => $this->formatQuestionnaireAnswer(
                $getAnswer('field_40b_yes_no', 'q40b'),
                $questionnaire->field_40_pwd_details ?? $questionnaire->question_details['q40b'] ?? ''
            ),
            'q40c_solo_parent' => $this->formatQuestionnaireAnswer(
                $getAnswer('field_40c_yes_no', 'q40c'),
                $questionnaire->field_40_solo_parent_details ?? $questionnaire->question_details['q40c'] ?? ''
            ),
        ];
    }

    /**
     * Format questionnaire answer with Yes/No and details
     */
    private function formatQuestionnaireAnswer($answer, $details)
    {
        if ($answer === null || $answer === '') {
            return '';
        }

        $yesNo = $answer ? 'Yes' : 'No';
        $details = !empty($details) ? ' - ' . $details : '';

        return $yesNo . $details;
    }

    /**
     * Get family background data with intelligent field mapping for export
     */
    private function getFamilyBackgroundDataForExport($familyBg)
    {
        // Handle both concatenated and separate name formats
        $fatherFullName = $familyBg->father_first_name ?
            trim($familyBg->father_first_name . ' ' . $familyBg->father_middle_name . ' ' . $familyBg->father_surname) :
            ($familyBg->father_name ?? '');

        $motherFullName = $familyBg->mother_first_name ?
            trim($familyBg->mother_first_name . ' ' . $familyBg->mother_middle_name . ' ' . ($familyBg->mother_maiden_name ?? $familyBg->mother_surname ?? '')) :
            ($familyBg->mother_name ?? '');

        return [
            'spouse_surname' => $familyBg->spouse_surname ?? '',
            'spouse_first_name' => $familyBg->spouse_first_name ?? '',
            'spouse_middle_name' => $familyBg->spouse_middle_name ?? '',
            'spouse_occupation' => $familyBg->spouse_occupation ?? '',
            'spouse_employer' => $familyBg->spouse_employer ?? '',
            'spouse_business_address' => $familyBg->spouse_business_address ?? '',
            'spouse_telephone_no' => $familyBg->spouse_telephone_no ?? '',
            'father_surname' => $familyBg->father_surname ?? '',
            'father_first_name' => $familyBg->father_first_name ?? '',
            'father_middle_name' => $familyBg->father_middle_name ?? '',
            'mother_surname' => $familyBg->mother_maiden_name ?? $familyBg->mother_surname ?? '',
            'mother_first_name' => $familyBg->mother_first_name ?? '',
            'mother_middle_name' => $familyBg->mother_middle_name ?? '',
        ];
    }

    /**
     * Format address from individual components
     */
    private function formatAddress($employee, $type)
    {
        $prefix = $type === 'res' ? 'res_' : 'perm_';
        $parts = array_filter([
            $employee->{$prefix . 'house_block_lot_no'},
            $employee->{$prefix . 'street'},
            $employee->{$prefix . 'subdivision_village'},
            $employee->{$prefix . 'barangay'},
            $employee->{$prefix . 'city_municipality'},
            $employee->{$prefix . 'province'},
            $employee->{$prefix . 'zip_code'},
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get eligibility data with intelligent field mapping for export
     */
    private function getEligibilityDataForExport($eligibility)
    {
        return [
            'eligibility_name' => $eligibility->eligibility_name ?? $eligibility->career_service ?? '',
            'rating' => $eligibility->rating ?? '',
            'date_of_examination' => $eligibility->date_of_examination?->format('m/d/Y') ?? $eligibility->date_acquired?->format('m/d/Y') ?? '',
            'place_of_examination' => $eligibility->place_of_examination ?? $eligibility->examination_place ?? '',
            'license_number' => $eligibility->license_number ?? '',
            'date_of_validity' => $eligibility->date_of_validity?->format('m/d/Y') ?? '',
        ];
    }

    /**
     * Generate headings for all PDS panels
     */
    public function headings(): array
    {
        $headings = [];

        // === PANEL 1: PERSONAL INFORMATION ===
        $headings = array_merge($headings, [
            'EMPLOYEE NO.', 'LAST NAME', 'FIRST NAME', 'MIDDLE NAME', 'NAME EXT.',
            'BIRTH DATE', 'PLACE OF BIRTH', 'SEX', 'CIVIL STATUS', 'CITIZENSHIP',
            'HEIGHT (m)', 'WEIGHT (kg)', 'BLOOD TYPE', 'GSIS ID NO.', 'PAG-IBIG ID NO.',
            'PHILHEALTH ID NO.', 'SSS NO.', 'TIN', 'AGENCY EMPLOYEE NO.',
            'RESIDENTIAL ADDRESS', 'RESIDENTIAL ZIP CODE', 'RESIDENTIAL TEL. NO.',
            'PERMANENT ADDRESS', 'PERMANENT ZIP CODE', 'PERMANENT TEL. NO.',
            'MOBILE NO.', 'EMAIL ADDRESS'
        ]);

        // === PANEL 2: FAMILY BACKGROUND ===
        $headings = array_merge($headings, [
            'SPOUSE LAST NAME', 'SPOUSE FIRST NAME', 'SPOUSE MIDDLE NAME',
            'SPOUSE OCCUPATION', 'SPOUSE EMPLOYER', 'SPOUSE BUSINESS ADDRESS',
            'SPOUSE TEL. NO.', 'FATHER LAST NAME', 'FATHER FIRST NAME',
            'FATHER MIDDLE NAME', 'FATHER EXT. NAME', 'MOTHER LAST NAME',
            'MOTHER FIRST NAME', 'MOTHER MIDDLE NAME'
        ]);

        // === CHILDREN ===
        for ($i = 1; $i <= 5; $i++) {
            $headings = array_merge($headings, [
                "CHILD {$i} FULL NAME", "CHILD {$i} BIRTH DATE"
            ]);
        }

        // === PANEL 3: EDUCATIONAL BACKGROUND ===
        foreach (['ELEMENTARY', 'SECONDARY', 'COLLEGE', 'VOCATIONAL', 'GRADUATE STUDIES'] as $level) {
            $headings = array_merge($headings, [
                "{$level} - SCHOOL NAME", "{$level} - DEGREE/COURSE",
                "{$level} - FROM", "{$level} - TO", "{$level} - YEAR GRADUATED",
                "{$level} - HIGHEST LEVEL", "{$level} - SCHOLARSHIP/HONORS"
            ]);
        }

        // === PANEL 4: CIVIL SERVICE ELIGIBILITY ===
        for ($i = 1; $i <= 3; $i++) {
            $headings = array_merge($headings, [
                "ELIGIBILITY {$i} - TYPE", "ELIGIBILITY {$i} - RATING",
                "ELIGIBILITY {$i} - DATE OF EXAM", "ELIGIBILITY {$i} - PLACE OF EXAM",
                "ELIGIBILITY {$i} - LICENSE NO.", "ELIGIBILITY {$i} - DATE OF VALIDITY"
            ]);
        }

        // === PANEL 5: WORK EXPERIENCE ===
        for ($i = 1; $i <= 5; $i++) {
            $headings = array_merge($headings, [
                "WORK {$i} - FROM", "WORK {$i} - TO", "WORK {$i} - POSITION",
                "WORK {$i} - DEPARTMENT/AGENCY", "WORK {$i} - MONTHLY SALARY",
                "WORK {$i} - SALARY GRADE", "WORK {$i} - STEP INCREMENT",
                "WORK {$i} - STATUS OF APPOINTMENT", "WORK {$i} - GOVT SERVICE"
            ]);
        }

        // === PANEL 6: VOLUNTARY WORK ===
        for ($i = 1; $i <= 2; $i++) {
            $headings = array_merge($headings, [
                "VOLUNTARY {$i} - ORGANIZATION", "VOLUNTARY {$i} - FROM",
                "VOLUNTARY {$i} - TO", "VOLUNTARY {$i} - NO. OF HOURS",
                "VOLUNTARY {$i} - POSITION/NATURE OF WORK"
            ]);
        }

        // === PANEL 7: TRAINING PROGRAMS ===
        for ($i = 1; $i <= 3; $i++) {
            $headings = array_merge($headings, [
                "TRAINING {$i} - TITLE", "TRAINING {$i} - FROM", "TRAINING {$i} - TO",
                "TRAINING {$i} - NO. OF HOURS", "TRAINING {$i} - TYPE OF LD",
                "TRAINING {$i} - CONDUCTED/SPONSORED BY"
            ]);
        }

        // === PANEL 8: OTHER INFORMATION ===
        $headings = array_merge($headings, [
            'SPECIAL SKILLS/HOBBIES', 'NON-ACADEMIC DISTINCTIONS',
            'MEMBERSHIP IN ASSOCIATIONS'
        ]);

        // === PANEL 9: REFERENCES ===
        for ($i = 1; $i <= 3; $i++) {
            $headings = array_merge($headings, [
                "REFERENCE {$i} - NAME", "REFERENCE {$i} - ADDRESS",
                "REFERENCE {$i} - TEL. NO."
            ]);
        }

        // === PANEL 10: GOVERNMENT ISSUED IDs ===
        $headings = array_merge($headings, [
            'GOVT ID TYPE', 'GOVT ID NUMBER', 'GOVT ID DATE ISSUED', 'GOVT ID PLACE ISSUED'
        ]);

        // === QUESTIONNAIRE ===
        $headings = array_merge($headings, [
            'Q34 - RELATIONSHIP TO APPOINTING AUTHORITY (3RD DEGREE)', 'Q34B - RELATIONSHIP (4TH DEGREE - LGU)',
            'Q35A - ADMINISTRATIVE OFFENSE', 'Q35B - CRIMINAL CHARGE',
            'Q36 - CONVICTION', 'Q37 - SEPARATION FROM SERVICE',
            'Q38A - ELECTION CANDIDACY', 'Q38B - RESIGNATION FOR CAMPAIGN',
            'Q39 - IMMIGRANT STATUS', 'Q40A - INDIGENOUS GROUP MEMBER',
            'Q40B - PERSON WITH DISABILITY', 'Q40C - SOLO PARENT'
        ]);

        // === DOCUMENT METADATA ===
        if ($this->includeMetadata) {
            $headings = array_merge($headings, [
                'TOTAL DOCUMENTS', 'PDS DOCUMENTS', 'TRANSCRIPTS', 'DIPLOMAS',
                'COE DOCUMENTS', 'TRAINING CERTIFICATES', 'ELIGIBILITY DOCUMENTS',
                'TOTAL FILE SIZE (bytes)'
            ]);
        }

        return $headings;
    }

    /**
     * Override base formatting with PDS-specific styles
     */
    public function styles(Worksheet $sheet): array
    {
        $styles = [];

        // Header row styling - maintain PDS blue color scheme
        $styles[1] = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        // Panel headers (every 26th row starting from row 1)
        $panelRows = [1, 27, 44, 54, 75, 101, 111, 121, 124, 133, 134];
        foreach ($panelRows as $rowNum) {
            $styles[$rowNum] = [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DDEBF7'],
                ],
            ];
        }

        return $styles;
    }

    /**
     * Custom column formatting for PDS data
     */
    protected function getColumnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_DATE_YYYYMMDD, // Birth Date
            'U' => NumberFormat::FORMAT_DATE_YYYYMMDD, // Employment Date (if applicable)
            // Add more date columns as needed based on the PDS data structure
        ];
    }

    /**
     * Custom column widths for PDS data
     */
    public function columnWidths(): array
    {
        $widths = [];

        // Personal Information columns - improved widths for better readability
        $personalInfoColumns = range('A', 'Z');
        $personalInfoColumns = array_merge($personalInfoColumns, ['AA', 'AB', 'AC', 'AD', 'AE']);

        foreach ($personalInfoColumns as $col) {
            $widths[$col] = 25; // Increased from 15 to 25 characters
        }

        // Set specific widths for important columns with longer content
        $widths['B'] = 25; // Last Name
        $widths['C'] = 25; // First Name
        $widths['G'] = 30; // Place of Birth
        $widths['U'] = 40; // Residential Address - increased
        $widths['X'] = 40; // Permanent Address - increased
        $widths['AE'] = 35; // Email - increased

        return $widths;
    }

    /**
     * Custom title for the export
     */
    public function title(): string
    {
        return 'Personal Data Sheet (PDS)';
    }

    /**
     * Override base event registration to add PDS-specific features
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Apply base formatting from trait
                $this->setPrintSettings($sheet);
                $this->enableAutoFilter($sheet);
                $this->setFrozenPanes($sheet);
                $this->adjustRowHeights($sheet);

                // Set PDS-specific margins
                $sheet->getPageMargins()
                    ->setTop(0.5)
                    ->setRight(0.25)
                    ->setBottom(0.5)
                    ->setLeft(0.25);
            },
        ];
    }
}