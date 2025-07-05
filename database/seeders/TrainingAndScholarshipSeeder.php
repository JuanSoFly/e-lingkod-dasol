<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TrainingProgram;
use App\Models\TrainingRequirement;
use App\Models\EmployeeTraining;
use App\Models\ScholarshipProgram;
use App\Models\EmployeeScholarship;
use App\Models\Employee;

class TrainingAndScholarshipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Government Training Programs
        $this->createGovernmentTrainingPrograms();
        
        // Create Training Requirements
        $this->createTrainingRequirements();
        
        // Create Sample Employee Trainings
        $this->createEmployeeTrainings();
        
        // Create Government Scholarship Programs
        $this->createGovernmentScholarshipPrograms();
        
        // Create Sample Employee Scholarships
        $this->createEmployeeScholarships();
    }

    private function createGovernmentTrainingPrograms()
    {
        $programs = [
            [
                'program_name' => 'Basic Civil Service Orientation',
                'program_code' => 'CSC-BCSO-2025',
                'provider' => 'CSC',
                'provider_organization' => 'Civil Service Commission',
                'training_type' => 'Orientation',
                'category' => 'Mandatory',
                'delivery_method' => 'Face-to-face',
                'duration_hours' => 40,
                'credit_hours' => 40.00,
                'duration_days' => 5,
                'description' => 'Basic orientation for newly appointed government employees covering civil service rules, ethics, and responsibilities.',
                'learning_objectives' => 'Understand civil service principles, code of conduct, and basic government procedures.',
                'target_participants' => 'Newly appointed permanent and temporary employees',
                'cost_per_participant' => 2500.00,
                'max_participants' => 50,
                'venue' => 'CSC Regional Office',
                'trainer_name' => 'CSC Training Officers',
                'status' => 'Active',
                'csc_recognized' => true,
                'dap_accredited' => false,
                'counts_towards_promotion' => true,
                'promotion_points' => 2,
                'has_certification' => true,
                'passing_score' => 75.00,
            ],
            [
                'program_name' => 'Leadership Excellence Program',
                'program_code' => 'DAP-LEP-2025',
                'provider' => 'DAP',
                'provider_organization' => 'Development Academy of the Philippines',
                'training_type' => 'Leadership',
                'category' => 'Career Development',
                'delivery_method' => 'Blended',
                'duration_hours' => 120,
                'credit_hours' => 120.00,
                'duration_days' => 15,
                'description' => 'Comprehensive leadership development program for middle and senior managers in government.',
                'learning_objectives' => 'Develop strategic thinking, decision-making, and team leadership skills.',
                'target_participants' => 'Division Chiefs, Department Heads, and Senior Staff',
                'cost_per_participant' => 25000.00,
                'max_participants' => 30,
                'venue' => 'DAP Tagaytay Campus',
                'trainer_name' => 'DAP Faculty and Guest Experts',
                'status' => 'Active',
                'csc_recognized' => true,
                'dap_accredited' => true,
                'counts_towards_promotion' => true,
                'promotion_points' => 10,
                'has_certification' => true,
                'passing_score' => 80.00,
            ],
            [
                'program_name' => 'Digital Government Transformation',
                'program_code' => 'DICT-DGT-2025',
                'provider' => 'Agency-specific',
                'provider_organization' => 'Department of Information and Communications Technology',
                'training_type' => 'Technical',
                'category' => 'Specialized Training',
                'delivery_method' => 'Online',
                'duration_hours' => 60,
                'credit_hours' => 60.00,
                'duration_days' => 8,
                'description' => 'Training on digital government services, cybersecurity, and data management for public sector.',
                'learning_objectives' => 'Understand digital transformation strategies and implement technology solutions in government.',
                'target_participants' => 'IT Staff, System Administrators, and Department Coordinators',
                'cost_per_participant' => 5000.00,
                'max_participants' => 100,
                'venue' => 'Online Platform',
                'trainer_name' => 'DICT Certified Trainers',
                'status' => 'Active',
                'csc_recognized' => true,
                'dap_accredited' => false,
                'counts_towards_promotion' => true,
                'promotion_points' => 5,
                'has_certification' => true,
                'passing_score' => 75.00,
            ],
            [
                'program_name' => 'Local Government Financial Management',
                'program_code' => 'DILG-LGFM-2025',
                'provider' => 'Agency-specific',
                'provider_organization' => 'Department of the Interior and Local Government',
                'training_type' => 'Professional Development',
                'category' => 'Specialized Training',
                'delivery_method' => 'Workshop',
                'duration_hours' => 80,
                'credit_hours' => 80.00,
                'duration_days' => 10,
                'description' => 'Comprehensive training on local government budget preparation, financial reporting, and audit procedures.',
                'learning_objectives' => 'Master LGU financial management, budgeting processes, and compliance requirements.',
                'target_participants' => 'Municipal Accountants, Budget Officers, and Treasury Staff',
                'cost_per_participant' => 8000.00,
                'max_participants' => 40,
                'venue' => 'DILG Regional Office',
                'trainer_name' => 'DILG Financial Management Experts',
                'status' => 'Active',
                'csc_recognized' => true,
                'dap_accredited' => false,
                'counts_towards_promotion' => true,
                'promotion_points' => 8,
                'has_certification' => true,
                'passing_score' => 80.00,
            ],
            [
                'program_name' => 'Gender and Development Mainstreaming',
                'program_code' => 'PCW-GADM-2025',
                'provider' => 'Agency-specific',
                'provider_organization' => 'Philippine Commission on Women',
                'training_type' => 'Compliance',
                'category' => 'Mandatory',
                'delivery_method' => 'Seminar',
                'duration_hours' => 16,
                'credit_hours' => 16.00,
                'duration_days' => 2,
                'description' => 'Training on gender-responsive governance, policy development, and program implementation.',
                'learning_objectives' => 'Understand GAD principles and implement gender-responsive programs and policies.',
                'target_participants' => 'All government employees (mandatory for GAD focal persons)',
                'cost_per_participant' => 1500.00,
                'max_participants' => 80,
                'venue' => 'LGU Conference Hall',
                'trainer_name' => 'PCW Gender Specialists',
                'status' => 'Active',
                'csc_recognized' => true,
                'dap_accredited' => false,
                'counts_towards_promotion' => false,
                'promotion_points' => 1,
                'has_certification' => true,
                'passing_score' => 70.00,
            ],
        ];

        foreach ($programs as $program) {
            TrainingProgram::create($program);
        }
    }

    private function createTrainingRequirements()
    {
        $requirements = [
            [
                'position_title' => 'Municipal Mayor',
                'department' => 'Office of the Mayor',
                'training_program_id' => 2, // Leadership Excellence Program
                'requirement_type' => 'Required for Promotion',
                'priority_level' => 'High',
                'frequency' => 'Every 3 Years',
                'deadline_months' => 6,
                'grace_period_days' => 30,
                'affects_promotion' => true,
                'affects_evaluation' => true,
                'career_level' => 'Executive',
                'min_passing_score' => 80.00,
                'requires_certification' => true,
                'csc_mandated' => false,
                'dap_required' => true,
                'legal_basis' => 'Local Government Code',
                'status' => 'Active',
            ],
            [
                'position_title' => 'Administrative Officer',
                'department' => null,
                'training_program_id' => 1, // Basic Civil Service Orientation
                'requirement_type' => 'Mandatory',
                'priority_level' => 'High',
                'frequency' => 'One-time',
                'deadline_months' => 3,
                'grace_period_days' => 15,
                'affects_promotion' => true,
                'affects_evaluation' => true,
                'career_level' => 'All Levels',
                'min_passing_score' => 75.00,
                'requires_certification' => true,
                'csc_mandated' => true,
                'dap_required' => false,
                'legal_basis' => 'CSC Resolution No. 1701077',
                'status' => 'Active',
            ],
            [
                'position_title' => 'Information Technology Officer',
                'department' => 'Management Information System',
                'training_program_id' => 3, // Digital Government Transformation
                'requirement_type' => 'Specialized',
                'priority_level' => 'High',
                'frequency' => 'Annual',
                'deadline_months' => 12,
                'grace_period_days' => 30,
                'affects_promotion' => true,
                'affects_evaluation' => true,
                'career_level' => 'Senior',
                'min_passing_score' => 75.00,
                'requires_certification' => true,
                'csc_mandated' => false,
                'dap_required' => false,
                'legal_basis' => 'DICT Memorandum on Digital Transformation',
                'status' => 'Active',
            ],
            [
                'position_title' => 'Municipal Accountant',
                'department' => 'Municipal Accountant Office',
                'training_program_id' => 4, // Local Government Financial Management
                'requirement_type' => 'Continuing Education',
                'priority_level' => 'High',
                'frequency' => 'Biennial',
                'deadline_months' => 24,
                'grace_period_days' => 45,
                'affects_promotion' => true,
                'affects_evaluation' => true,
                'career_level' => 'Senior',
                'min_passing_score' => 80.00,
                'requires_certification' => true,
                'csc_mandated' => false,
                'dap_required' => false,
                'legal_basis' => 'DILG Memorandum on LGU Financial Management',
                'status' => 'Active',
            ],
            [
                'position_title' => 'GAD Focal Person',
                'department' => null,
                'training_program_id' => 5, // Gender and Development Mainstreaming
                'requirement_type' => 'Mandatory',
                'priority_level' => 'Medium',
                'frequency' => 'Annual',
                'deadline_months' => 6,
                'grace_period_days' => 20,
                'affects_promotion' => false,
                'affects_evaluation' => true,
                'career_level' => 'All Levels',
                'min_passing_score' => 70.00,
                'requires_certification' => true,
                'csc_mandated' => true,
                'dap_required' => false,
                'legal_basis' => 'RA 9710 - Magna Carta of Women',
                'status' => 'Active',
            ],
        ];

        foreach ($requirements as $requirement) {
            TrainingRequirement::create($requirement);
        }
    }

    private function createEmployeeTrainings()
    {
        $employees = Employee::take(10)->get();
        
        if ($employees->isEmpty()) {
            return; // No employees to assign trainings to
        }

        $trainingPrograms = TrainingProgram::all();
        
        foreach ($employees->take(5) as $employee) {
            foreach ($trainingPrograms->take(2) as $program) {
                EmployeeTraining::create([
                    'employee_id' => $employee->id,
                    'training_program_id' => $program->id,
                    'enrollment_date' => now()->subDays(rand(30, 180)),
                    'enrollment_status' => 'Approved',
                    'start_date' => now()->subDays(rand(10, 60)),
                    'end_date' => now()->subDays(rand(1, 30)),
                    'completion_status' => ['Completed', 'In Progress', 'Not Started'][rand(0, 2)],
                    'attendance_percentage' => rand(80, 100),
                    'hours_attended' => rand(30, $program->duration_hours),
                    'final_score' => rand(70, 95),
                    'passing_status' => ['Passed', 'Pending'][rand(0, 1)],
                    'certificate_number' => 'CERT-' . strtoupper(substr($program->program_code, 0, 3)) . '-' . $employee->id . '-' . date('Y'),
                    'certificate_date' => now()->subDays(rand(1, 15)),
                    'trainer_name' => $program->trainer_name,
                    'delivery_mode' => $program->delivery_method,
                    'training_cost' => $program->cost_per_participant,
                    'cost_center' => 'Department Budget',
                    'evaluation_rating' => rand(3, 5),
                    'would_recommend' => true,
                    'mandatory_compliance' => rand(0, 1),
                    'counts_towards_promotion' => $program->counts_towards_promotion,
                    'promotion_points_earned' => $program->promotion_points,
                    'record_status' => 'Active',
                ]);
            }
        }
    }

    private function createGovernmentScholarshipPrograms()
    {
        $programs = [
            [
                'program_name' => 'DOST Science and Technology Scholarship',
                'program_code' => 'DOST-STS-2025',
                'funding_agency' => 'Department of Science and Technology',
                'implementing_agency' => 'Science Education Institute',
                'scholarship_type' => 'Full Scholarship',
                'degree_level' => 'Master Degree',
                'field_of_study' => 'Science, Technology, Engineering, Mathematics',
                'duration_years' => 2,
                'total_budget' => 50000000.00,
                'per_scholar_budget' => 500000.00,
                'covers_tuition' => true,
                'covers_living_allowance' => true,
                'covers_transportation' => true,
                'covers_books_materials' => true,
                'covers_research_expenses' => true,
                'monthly_allowance' => 15000.00,
                'book_allowance' => 20000.00,
                'thesis_allowance' => 30000.00,
                'eligibility_criteria' => 'Permanent government employees in science and technology positions with at least 2 years of service and Very Satisfactory performance rating.',
                'min_years_service' => 2,
                'max_years_service' => 25,
                'min_age' => 25,
                'max_age' => 45,
                'min_performance_rating' => 4.0,
                'position_requirements' => 'Science Research Specialist, Engineer, IT Officer',
                'requires_entrance_exam' => true,
                'requires_interview' => true,
                'available_slots' => 100,
                'service_obligation_years' => 4,
                'bond_amount' => 500000.00,
                'status' => 'Active',
                'daps_approved' => true,
                'csc_approved' => true,
                'dbm_approved' => true,
                'legal_basis' => 'RA 7687 - Science and Technology Scholarship Act',
                'is_international' => false,
            ],
            [
                'program_name' => 'Fulbright Philippines Study Program',
                'program_code' => 'FULB-PSP-2025',
                'funding_agency' => 'US Embassy Philippines',
                'implementing_agency' => 'Philippine-American Educational Foundation',
                'scholarship_type' => 'Full Scholarship',
                'degree_level' => 'Doctoral Degree',
                'field_of_study' => 'Public Administration, Education, Social Sciences',
                'duration_years' => 4,
                'total_budget' => 100000000.00,
                'per_scholar_budget' => 5000000.00,
                'covers_tuition' => true,
                'covers_living_allowance' => true,
                'covers_transportation' => true,
                'covers_books_materials' => true,
                'covers_research_expenses' => true,
                'covers_conference_fees' => true,
                'monthly_allowance' => 100000.00, // USD 2000 equivalent
                'travel_allowance' => 250000.00,
                'eligibility_criteria' => 'Senior government officials with leadership potential, excellent academic record, and commitment to public service.',
                'min_years_service' => 5,
                'max_years_service' => 20,
                'min_age' => 28,
                'max_age' => 50,
                'min_performance_rating' => 4.5,
                'position_requirements' => 'Department Head, Division Chief, Assistant/Deputy Director',
                'requires_entrance_exam' => false,
                'requires_interview' => true,
                'requires_medical_exam' => true,
                'requires_language_proficiency' => true,
                'language_requirements' => 'TOEFL iBT score of 100 or IELTS score of 7.0',
                'available_slots' => 20,
                'service_obligation_years' => 8,
                'bond_amount' => 5000000.00,
                'status' => 'Active',
                'daps_approved' => true,
                'csc_approved' => true,
                'dbm_approved' => true,
                'legal_basis' => 'Fulbright Educational Exchange Program Agreement',
                'is_international' => true,
                'participating_countries' => 'United States of America',
                'requires_visa' => true,
            ],
            [
                'program_name' => 'Japan-Philippines Economic Partnership Agreement Scholarship',
                'program_code' => 'JPEPA-SCHOL-2025',
                'funding_agency' => 'Japan International Cooperation Agency',
                'implementing_agency' => 'Department of Foreign Affairs',
                'scholarship_type' => 'Full Scholarship',
                'degree_level' => 'Master Degree',
                'field_of_study' => 'Public Policy, Economic Development, Urban Planning',
                'duration_years' => 2,
                'total_budget' => 75000000.00,
                'per_scholar_budget' => 1500000.00,
                'covers_tuition' => true,
                'covers_living_allowance' => true,
                'covers_transportation' => true,
                'covers_books_materials' => true,
                'monthly_allowance' => 60000.00, // JPY 120,000 equivalent
                'travel_allowance' => 150000.00,
                'eligibility_criteria' => 'Government employees in economic development, planning, or policy positions with strong academic background.',
                'min_years_service' => 3,
                'max_years_service' => 15,
                'min_age' => 26,
                'max_age' => 40,
                'min_performance_rating' => 4.0,
                'position_requirements' => 'Planning Officer, Economic Development Officer, Policy Analyst',
                'requires_entrance_exam' => false,
                'requires_interview' => true,
                'requires_medical_exam' => true,
                'requires_language_proficiency' => true,
                'language_requirements' => 'Basic Japanese language proficiency or English (TOEFL 550)',
                'available_slots' => 50,
                'service_obligation_years' => 4,
                'bond_amount' => 1500000.00,
                'status' => 'Active',
                'daps_approved' => true,
                'csc_approved' => true,
                'dbm_approved' => true,
                'legal_basis' => 'Japan-Philippines Economic Partnership Agreement',
                'is_international' => true,
                'participating_countries' => 'Japan',
                'requires_visa' => true,
            ],
            [
                'program_name' => 'Local Government Academy Leadership Development',
                'program_code' => 'LGA-LD-2025',
                'funding_agency' => 'Department of the Interior and Local Government',
                'implementing_agency' => 'Local Government Academy',
                'scholarship_type' => 'Study Leave with Pay',
                'degree_level' => 'Master Degree',
                'field_of_study' => 'Public Administration, Local Government Management',
                'duration_years' => 2,
                'total_budget' => 20000000.00,
                'per_scholar_budget' => 200000.00,
                'covers_tuition' => true,
                'covers_living_allowance' => false,
                'covers_transportation' => false,
                'covers_books_materials' => true,
                'book_allowance' => 15000.00,
                'thesis_allowance' => 25000.00,
                'eligibility_criteria' => 'Local government unit employees with leadership potential and commitment to local governance excellence.',
                'min_years_service' => 3,
                'max_years_service' => 20,
                'min_age' => 25,
                'max_age' => 45,
                'min_performance_rating' => 4.0,
                'position_requirements' => 'Supervisory and Management positions in LGUs',
                'requires_entrance_exam' => false,
                'requires_interview' => true,
                'available_slots' => 100,
                'service_obligation_years' => 4,
                'bond_amount' => 200000.00,
                'status' => 'Active',
                'daps_approved' => true,
                'csc_approved' => true,
                'dbm_approved' => true,
                'legal_basis' => 'Local Government Code and DILG Memorandum',
                'is_international' => false,
            ],
            [
                'program_name' => 'Commission on Higher Education Faculty Development',
                'program_code' => 'CHED-FDP-2025',
                'funding_agency' => 'Commission on Higher Education',
                'implementing_agency' => 'Commission on Higher Education',
                'scholarship_type' => 'Partial Scholarship',
                'degree_level' => 'Doctoral Degree',
                'field_of_study' => 'Education, Research, Academic Fields',
                'duration_years' => 4,
                'total_budget' => 30000000.00,
                'per_scholar_budget' => 300000.00,
                'covers_tuition' => true,
                'covers_living_allowance' => false,
                'covers_books_materials' => true,
                'covers_research_expenses' => true,
                'book_allowance' => 20000.00,
                'thesis_allowance' => 50000.00,
                'eligibility_criteria' => 'Faculty members of state universities and colleges with commitment to academic excellence and research.',
                'min_years_service' => 2,
                'max_years_service' => 25,
                'min_age' => 28,
                'max_age' => 50,
                'min_performance_rating' => 4.0,
                'position_requirements' => 'Assistant Professor, Associate Professor, Professor',
                'requires_entrance_exam' => true,
                'requires_interview' => true,
                'available_slots' => 100,
                'service_obligation_years' => 6,
                'bond_amount' => 300000.00,
                'status' => 'Active',
                'daps_approved' => true,
                'csc_approved' => true,
                'dbm_approved' => true,
                'legal_basis' => 'RA 7722 - Higher Education Act',
                'is_international' => false,
            ],
        ];

        foreach ($programs as $program) {
            ScholarshipProgram::create($program);
        }
    }

    private function createEmployeeScholarships()
    {
        $employees = Employee::take(8)->get();
        
        if ($employees->isEmpty()) {
            return; // No employees to assign scholarships to
        }

        $scholarshipPrograms = ScholarshipProgram::all();
        
        foreach ($employees->take(4) as $employee) {
            $program = $scholarshipPrograms->random();
            
            EmployeeScholarship::create([
                'employee_id' => $employee->id,
                'scholarship_program_id' => $program->id,
                'application_date' => now()->subDays(rand(60, 365)),
                'application_reference' => 'APP-' . strtoupper(substr($program->program_code, 0, 4)) . '-' . $employee->id . '-' . date('Y'),
                'approval_status' => ['Approved', 'Under Review', 'Conditionally Approved'][rand(0, 2)],
                'approved_by' => 'HR Director',
                'approved_at' => now()->subDays(rand(30, 180)),
                'start_date' => now()->subDays(rand(10, 90)),
                'expected_completion' => now()->addMonths($program->duration_years * 12),
                'institution_name' => $program->is_international 
                    ? ['Harvard University', 'University of Tokyo', 'Stanford University'][rand(0, 2)]
                    : ['University of the Philippines', 'Ateneo de Manila University', 'De La Salle University'][rand(0, 2)],
                'institution_country' => $program->is_international 
                    ? ($program->participating_countries ?? 'United States')
                    : 'Philippines',
                'course_title' => 'Master of Public Administration',
                'degree_program' => $program->degree_level,
                'major_field' => explode(',', $program->field_of_study)[0],
                'academic_status' => ['Enrolled', 'In Progress', 'Not Started'][rand(0, 2)],
                'gpa' => rand(300, 400) / 100, // 3.00 to 4.00
                'current_year_level' => rand(1, $program->duration_years),
                'completion_percentage' => rand(10, 80),
                'total_scholarship_amount' => $program->per_scholar_budget,
                'amount_received' => $program->per_scholar_budget * (rand(20, 60) / 100),
                'monthly_stipend' => $program->monthly_allowance,
                'tuition_covered' => $program->per_scholar_budget * 0.6, // 60% for tuition
                'service_obligation_years' => $program->service_obligation_years,
                'bond_amount' => $program->bond_amount,
                'compliance_status' => 'Not Applicable', // Not yet graduated
                'reporting_compliance' => 'Compliant',
                'hr_officer' => 'HR Officer',
                'record_status' => 'Active',
                'required_documents' => [
                    'Application Form',
                    'Transcript of Records',
                    'Service Record',
                    'Performance Rating',
                    'Medical Certificate',
                    'Recommendation Letters'
                ],
                'submitted_documents' => [
                    'Application Form',
                    'Transcript of Records',
                    'Service Record',
                    'Performance Rating'
                ],
                'missing_documents' => [
                    'Medical Certificate',
                    'Recommendation Letters'
                ],
            ]);
        }
    }
}