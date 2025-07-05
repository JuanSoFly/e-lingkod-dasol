<?php

namespace Database\Seeders;

use App\Models\DocumentLink;
use App\Models\DocumentVersion;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\LeaveApplication;
use App\Models\PerformanceReview;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentVersionAndLinkSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Get sample data
            $employees = Employee::limit(5)->get();
            $user = User::first();
            
            if ($employees->isEmpty() || !$user) {
                $this->command->warn('No employees or users found. Please run the main seeders first.');
                return;
            }

            foreach ($employees as $employee) {
                $this->createDocumentVersionsForEmployee($employee, $user);
                $this->createDocumentLinksForEmployee($employee, $user);
            }
        });
    }

    private function createDocumentVersionsForEmployee(Employee $employee, User $user): void
    {
        $documents = $employee->documents()->limit(3)->get();
        
        foreach ($documents as $document) {
            // Create 2-3 versions for each document
            $versionCount = rand(2, 3);
            
            for ($i = 1; $i <= $versionCount; $i++) {
                $isCurrentVersion = ($i === $versionCount); // Last version is current
                
                DocumentVersion::create([
                    'original_document_id' => $document->id,
                    'parent_version_id' => $i > 1 ? DocumentVersion::where('original_document_id', $document->id)->orderBy('version_number', 'desc')->first()?->id : null,
                    'version_number' => $i,
                    'is_current_version' => $isCurrentVersion,
                    'file_name' => $document->file_name,
                    'file_path' => $document->file_path . "_v{$i}",
                    'file_size' => $document->file_size ?: rand(1024, 10485760), // 1KB to 10MB
                    'mime_type' => $document->mime_type ?: 'application/pdf',
                    'file_hash' => hash('sha256', $document->file_name . $i . time()),
                    'checksum' => hash('md5', $document->file_name . $i),
                    'change_reason' => $this->getRandomChangeReason($i),
                    'version_notes' => $this->getRandomVersionNotes($i),
                    'uploaded_by' => $user->id,
                    'uploaded_at' => now()->subDays(rand(1, 30)),
                    'approved_by' => $user->id,
                    'approved_at' => now()->subDays(rand(0, 29)),
                    'approval_status' => 'approved',
                    'can_rollback' => true,
                    'rollback_until' => now()->addMonths(6),
                ]);
            }
        }
    }

    private function createDocumentLinksForEmployee(Employee $employee, User $user): void
    {
        $documents = $employee->documents;
        $leaveApplications = $employee->leaveApplications()->limit(2)->get();
        $performanceReviews = $employee->performanceReviews()->limit(1)->get();
        
        // Link documents to leave applications
        foreach ($leaveApplications as $leave) {
            $supportingDocs = $documents->where('document_type', 'Medical Certificate')
                                      ->merge($documents->where('document_type', 'Leave Form'))
                                      ->take(2);
            
            foreach ($supportingDocs as $doc) {
                $linkType = str_contains(strtolower($doc->document_type), 'medical') 
                    ? 'medical_certificate' 
                    : 'leave_supporting_doc';
                
                DocumentLink::create([
                    'source_type' => EmployeeDocument::class,
                    'source_id' => $doc->id,
                    'target_type' => LeaveApplication::class,
                    'target_id' => $leave->id,
                    'link_type' => $linkType,
                    'relationship_strength' => 'strong',
                    'is_automatic' => rand(0, 1) === 1,
                    'is_bidirectional' => true,
                    'is_primary' => true,
                    'link_metadata' => [
                        'matching_criteria' => ['date_proximity', 'employee_match', 'document_type_match'],
                        'confidence_reason' => 'Document uploaded within leave application period'
                    ],
                    'link_reason' => 'Supporting document for leave application',
                    'confidence_score' => rand(75, 100) / 100,
                    'status' => 'active',
                    'validated_at' => now(),
                    'validated_by' => $user->id,
                    'last_verified_at' => now(),
                    'verification_count' => 1,
                    'created_by' => $user->id,
                ]);
            }
        }

        // Link documents to performance reviews
        foreach ($performanceReviews as $review) {
            $evidenceDocs = $documents->where('document_type', 'Training Certificate')
                                    ->merge($documents->where('document_type', 'Performance Report'))
                                    ->take(1);
            
            foreach ($evidenceDocs as $doc) {
                DocumentLink::create([
                    'source_type' => EmployeeDocument::class,
                    'source_id' => $doc->id,
                    'target_type' => PerformanceReview::class,
                    'target_id' => $review->id,
                    'link_type' => 'performance_evidence',
                    'relationship_strength' => 'medium',
                    'is_automatic' => true,
                    'is_bidirectional' => true,
                    'is_primary' => false,
                    'link_metadata' => [
                        'matching_criteria' => ['document_type_match', 'employee_match'],
                        'confidence_reason' => 'Training certificate supports performance evaluation'
                    ],
                    'link_reason' => 'Evidence document for performance review',
                    'confidence_score' => rand(60, 90) / 100,
                    'status' => 'active',
                    'validated_at' => now(),
                    'validated_by' => $user->id,
                    'last_verified_at' => now(),
                    'verification_count' => 1,
                    'created_by' => $user->id,
                ]);
            }
        }

        // Create some education credential links
        $educationRecords = $employee->education()->limit(1)->get();
        foreach ($educationRecords as $education) {
            $credentialDocs = $documents->where('document_type', 'Educational Certificate')
                                      ->merge($documents->where('document_type', 'Diploma'))
                                      ->take(1);
            
            foreach ($credentialDocs as $doc) {
                DocumentLink::create([
                    'source_type' => EmployeeDocument::class,
                    'source_id' => $doc->id,
                    'target_type' => get_class($education),
                    'target_id' => $education->id,
                    'link_type' => 'education_credential',
                    'relationship_strength' => 'strong',
                    'is_automatic' => false,
                    'is_bidirectional' => true,
                    'is_primary' => true,
                    'link_metadata' => [
                        'matching_criteria' => ['manual_verification'],
                        'confidence_reason' => 'Manually verified educational credential'
                    ],
                    'link_reason' => 'Educational credential document',
                    'confidence_score' => 1.0,
                    'status' => 'active',
                    'validated_at' => now(),
                    'validated_by' => $user->id,
                    'last_verified_at' => now(),
                    'verification_count' => 1,
                    'created_by' => $user->id,
                ]);
            }
        }

        // Create some pending validation links
        $remainingDocs = $documents->take(1);
        foreach ($remainingDocs as $doc) {
            DocumentLink::create([
                'source_type' => EmployeeDocument::class,
                'source_id' => $doc->id,
                'target_type' => Employee::class,
                'target_id' => $employee->id,
                'link_type' => 'custom_link',
                'relationship_strength' => 'weak',
                'is_automatic' => true,
                'is_bidirectional' => false,
                'is_primary' => false,
                'link_metadata' => [
                    'matching_criteria' => ['low_confidence_match'],
                    'confidence_reason' => 'Potential relationship needs validation'
                ],
                'link_reason' => 'Auto-detected potential relationship',
                'confidence_score' => rand(30, 49) / 100,
                'status' => 'pending_validation',
                'requires_manual_approval' => true,
                'last_verified_at' => null,
                'verification_count' => 0,
                'created_by' => $user->id,
            ]);
        }
    }

    private function getRandomChangeReason(int $versionNumber): string
    {
        $reasons = [
            'Initial upload',
            'Document correction',
            'Updated information',
            'Quality improvement',
            'Formatting changes',
            'Added signatures',
            'Compliance update',
            'Error correction',
            'Additional information added',
            'Document standardization'
        ];

        return $versionNumber === 1 ? 'Initial upload' : $reasons[array_rand($reasons)];
    }

    private function getRandomVersionNotes(int $versionNumber): string
    {
        $notes = [
            'Initial version uploaded to system',
            'Corrected employee information',
            'Updated format to match new standards',
            'Added missing signatures and stamps',
            'Improved document quality and readability',
            'Fixed date formatting issues',
            'Added required compliance information',
            'Corrected spelling and grammar errors',
            'Updated contact information',
            'Standardized document layout'
        ];

        return $versionNumber === 1 ? 'Initial version uploaded to system' : $notes[array_rand($notes)];
    }
}