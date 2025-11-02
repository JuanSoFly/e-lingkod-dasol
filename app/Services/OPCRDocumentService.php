<?php

namespace App\Services;

use App\Models\OPCRWorkflow;
use App\Models\PerformanceTarget;
use App\Models\SuccessIndicator;
use App\Models\EmployeeDocument;
use App\Models\DocumentVersion;
use App\Models\DocumentLink;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class OPCRDocumentService
{
    private DocumentManagementService $documentService;
    private AuditTrailService $auditTrailService;

    public function __construct(
        DocumentManagementService $documentService,
        AuditTrailService $auditTrailService
    ) {
        $this->documentService = $documentService;
        $this->auditTrailService = $auditTrailService;
    }

    /**
     * Upload evidence document for success indicator
     */
    public function uploadEvidenceDocument(
        SuccessIndicator $successIndicator,
        UploadedFile $file,
        User $uploader,
        array $metadata = []
    ): DocumentVersion {
        return DB::transaction(function () use ($successIndicator, $file, $uploader, $metadata) {
            // Create employee document for the evidence
            $employeeDocument = EmployeeDocument::create([
                'employee_id' => $successIndicator->mfo->office->department_head_id ?? null,
                'document_type' => 'opcr_evidence',
                'document_name' => $metadata['document_name'] ?? $file->getClientOriginalName(),
                'description' => $metadata['description'] ?? "Evidence document for {$successIndicator->title}",
                'file_path' => '',
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_by' => $uploader->id,
                'is_confidential' => $metadata['is_confidential'] ?? false,
                'expires_at' => $metadata['expires_at'] ?? null,
                'remarks' => $metadata['remarks'] ?? null,
            ]);

            // Create document version
            $version = $this->documentService->createDocumentVersion(
                $employeeDocument,
                $file,
                $uploader,
                $metadata['change_reason'] ?? 'OPCR evidence document upload',
                $metadata['version_notes'] ?? "Evidence for {$successIndicator->code}: {$successIndicator->title}",
                true // Auto-approve for OPCR documents
            );

            // Create document link to success indicator
            DocumentLink::create([
                'source_type' => SuccessIndicator::class,
                'source_id' => $successIndicator->id,
                'document_version_id' => $version->id,
                'link_type' => 'evidence_document',
                'relationship_description' => 'Evidence document supporting success indicator accomplishment',
                'created_by' => $uploader->id,
            ]);

            // Log the document upload
            $this->auditTrailService->logOPCRActivity(
                'evidence_document_uploaded',
                $uploader,
                [
                    'success_indicator_id' => $successIndicator->id,
                    'success_indicator_code' => $successIndicator->code,
                    'document_version_id' => $version->id,
                    'employee_document_id' => $employeeDocument->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                ]
            );

            return $version;
        });
    }

    /**
     * Upload supporting document for OPCR workflow
     */
    public function uploadWorkflowDocument(
        OPCRWorkflow $workflow,
        UploadedFile $file,
        User $uploader,
        string $documentType,
        array $metadata = []
    ): DocumentVersion {
        return DB::transaction(function () use ($workflow, $file, $uploader, $documentType, $metadata) {
            // Create employee document for the workflow
            $employeeDocument = EmployeeDocument::create([
                'employee_id' => $workflow->committedBy->employee->id ?? null,
                'document_type' => $documentType,
                'document_name' => $metadata['document_name'] ?? $file->getClientOriginalName(),
                'description' => $metadata['description'] ?? "{$documentType} for OPCR workflow",
                'file_path' => '',
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_by' => $uploader->id,
                'is_confidential' => $metadata['is_confidential'] ?? false,
                'expires_at' => $metadata['expires_at'] ?? null,
                'remarks' => $metadata['remarks'] ?? null,
            ]);

            // Create document version
            $version = $this->documentService->createDocumentVersion(
                $employeeDocument,
                $file,
                $uploader,
                $metadata['change_reason'] ?? "OPCR workflow document upload: {$documentType}",
                $metadata['version_notes'] ?? "Supporting document for {$workflow->office->name} OPCR",
                true // Auto-approve for OPCR documents
            );

            // Create document link to workflow
            DocumentLink::create([
                'source_type' => OPCRWorkflow::class,
                'source_id' => $workflow->id,
                'document_version_id' => $version->id,
                'link_type' => $documentType,
                'relationship_description' => "Supporting document for OPCR workflow: {$documentType}",
                'created_by' => $uploader->id,
            ]);

            // Log the document upload
            $this->auditTrailService->logOPCRActivity(
                'workflow_document_uploaded',
                $uploader,
                [
                    'workflow_id' => $workflow->id,
                    'workflow_state' => $workflow->workflow_state,
                    'document_type' => $documentType,
                    'document_version_id' => $version->id,
                    'employee_document_id' => $employeeDocument->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                ]
            );

            return $version;
        });
    }

    /**
     * Get evidence documents for success indicator
     */
    public function getEvidenceDocuments(SuccessIndicator $successIndicator): array
    {
        return DocumentLink::with(['documentVersion.employeeDocument', 'documentVersion.approvedBy'])
            ->where('source_type', SuccessIndicator::class)
            ->where('source_id', $successIndicator->id)
            ->where('link_type', 'evidence_document')
            ->get()
            ->map(function ($link) {
                $version = $link->documentVersion;
                $document = $version->employeeDocument;

                return [
                    'document_link_id' => $link->id,
                    'document_version_id' => $version->id,
                    'employee_document_id' => $document->id,
                    'document_name' => $document->document_name,
                    'description' => $document->description,
                    'file_name' => $version->file_name,
                    'file_size' => $version->file_size,
                    'mime_type' => $version->mime_type,
                    'file_url' => Storage::url($version->file_path),
                    'uploaded_by' => $document->uploadedBy->name ?? 'Unknown',
                    'uploaded_at' => $version->created_at->format('Y-m-d H:i:s'),
                    'approved_by' => $version->approvedBy->name ?? null,
                    'approved_at' => $version->approved_at?->format('Y-m-d H:i:s'),
                    'relationship_description' => $link->relationship_description,
                ];
            })
            ->toArray();
    }

    /**
     * Get workflow documents
     */
    public function getWorkflowDocuments(OPCRWorkflow $workflow, ?string $documentType = null): array
    {
        $query = DocumentLink::with(['documentVersion.employeeDocument', 'documentVersion.approvedBy'])
            ->where('source_type', OPCRWorkflow::class)
            ->where('source_id', $workflow->id);

        if ($documentType) {
            $query->where('link_type', $documentType);
        }

        return $query->get()
            ->map(function ($link) {
                $version = $link->documentVersion;
                $document = $version->employeeDocument;

                return [
                    'document_link_id' => $link->id,
                    'document_version_id' => $version->id,
                    'employee_document_id' => $document->id,
                    'document_type' => $document->document_type,
                    'document_name' => $document->document_name,
                    'description' => $document->description,
                    'file_name' => $version->file_name,
                    'file_size' => $version->file_size,
                    'mime_type' => $version->mime_type,
                    'file_url' => Storage::url($version->file_path),
                    'uploaded_by' => $document->uploadedBy->name ?? 'Unknown',
                    'uploaded_at' => $version->created_at->format('Y-m-d H:i:s'),
                    'approved_by' => $version->approvedBy->name ?? null,
                    'approved_at' => $version->approved_at?->format('Y-m-d H:i:s'),
                    'relationship_description' => $link->relationship_description,
                ];
            })
            ->toArray();
    }

    /**
     * Delete evidence document
     */
    public function deleteEvidenceDocument(int $documentLinkId, User $deleter): bool
    {
        return DB::transaction(function () use ($documentLinkId, $deleter) {
            $link = DocumentLink::with(['documentVersion', 'documentVersion.employeeDocument'])
                ->findOrFail($documentLinkId);

            // Check if can delete (only uploader or admin can delete)
            if ($link->documentVersion->uploaded_by !== $deleter->id && !$deleter->hasRole(['Super Admin', 'HR Admin'])) {
                throw new \Illuminate\Auth\Access\AuthorizationException('You can only delete your own uploaded documents');
            }

            $documentInfo = [
                'success_indicator_id' => $link->source_id,
                'document_name' => $link->documentVersion->file_name,
                'deleted_by' => $deleter->name,
            ];

            // Delete the link
            $link->delete();

            // Log the deletion
            $this->auditTrailService->logOPCRActivity(
                'evidence_document_deleted',
                $deleter,
                $documentInfo
            );

            return true;
        });
    }

    /**
     * Delete workflow document
     */
    public function deleteWorkflowDocument(int $documentLinkId, User $deleter): bool
    {
        return DB::transaction(function () use ($documentLinkId, $deleter) {
            $link = DocumentLink::with(['documentVersion', 'documentVersion.employeeDocument'])
                ->findOrFail($documentLinkId);

            // Check if can delete (only uploader or admin can delete)
            if ($link->documentVersion->uploaded_by !== $deleter->id && !$deleter->hasRole(['Super Admin', 'HR Admin'])) {
                throw new \Illuminate\Auth\Access\AuthorizationException('You can only delete your own uploaded documents');
            }

            $documentInfo = [
                'workflow_id' => $link->source_id,
                'document_type' => $link->link_type,
                'document_name' => $link->documentVersion->file_name,
                'deleted_by' => $deleter->name,
            ];

            // Delete the link
            $link->delete();

            // Log the deletion
            $this->auditTrailService->logOPCRActivity(
                'workflow_document_deleted',
                $deleter,
                $documentInfo
            );

            return true;
        });
    }

    /**
     * Get document statistics for OPCR
     */
    public function getDocumentStatistics(?int $periodId = null, ?int $officeId = null): array
    {
        $query = DocumentLink::query();

        if ($periodId) {
            $query->whereHas('source', function ($q) use ($periodId) {
                $q->where('period_id', $periodId);
            });
        }

        if ($officeId) {
            $query->where(function ($q) use ($officeId) {
                $q->whereHas('source', function ($subQ) use ($officeId) {
                    $subQ->where('office_id', $officeId);
                })->orWhereHas('source.mfo', function ($subQ) use ($officeId) {
                    $subQ->where('office_id', $officeId);
                });
            });
        }

        $totalDocuments = $query->count();

        $documentsByType = $query->select('link_type', DB::raw('count(*) as count'))
            ->groupBy('link_type')
            ->pluck('count', 'link_type')
            ->toArray();

        $totalSize = DocumentLink::query()
            ->when($periodId, function ($q) use ($periodId) {
                $q->whereHas('source', function ($subQ) use ($periodId) {
                    $subQ->where('period_id', $periodId);
                });
            })
            ->when($officeId, function ($q) use ($officeId) {
                $q->where(function ($subQ) use ($officeId) {
                    $subQ->whereHas('source', function ($subSubQ) use ($officeId) {
                        $subSubQ->where('office_id', $officeId);
                    })->orWhereHas('source.mfo', function ($subSubQ) use ($officeId) {
                        $subSubQ->where('office_id', $officeId);
                    });
                });
            })
            ->join('document_versions', 'document_links.document_version_id', '=', 'document_versions.id')
            ->sum('document_versions.file_size');

        return [
            'total_documents' => $totalDocuments,
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / (1024 * 1024), 2),
            'documents_by_type' => $documentsByType,
            'most_common_type' => $documentsByType ? array_keys($documentsByType, max($documentsByType))[0] : null,
        ];
    }

    /**
     * Get recent document uploads for OPCR
     */
    public function getRecentDocumentUploads(int $limit = 10, ?int $periodId = null, ?int $officeId = null): array
    {
        $query = DocumentLink::with(['documentVersion.uploadedBy', 'source'])
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($periodId) {
            $query->whereHas('source', function ($q) use ($periodId) {
                $q->where('period_id', $periodId);
            });
        }

        if ($officeId) {
            $query->where(function ($q) use ($officeId) {
                $q->whereHas('source', function ($subQ) use ($officeId) {
                    $subQ->where('office_id', $officeId);
                })->orWhereHas('source.mfo', function ($subQ) use ($officeId) {
                    $subQ->where('office_id', $officeId);
                });
            });
        }

        return $query->get()
            ->map(function ($link) {
                $version = $link->documentVersion;
                $source = $link->source;

                return [
                    'document_link_id' => $link->id,
                    'document_name' => $version->file_name,
                    'document_type' => $link->link_type,
                    'source_type' => class_basename($source),
                    'source_info' => [
                        'id' => $source->id,
                        'name' => method_exists($source, 'getTitle') ? $source->getTitle() :
                               (method_exists($source, 'name') ? $source->name :
                               (method_exists($source, 'code') ? $source->code : 'Unknown')),
                    ],
                    'uploaded_by' => $version->uploadedBy->name ?? 'Unknown',
                    'uploaded_at' => $link->created_at->format('Y-m-d H:i:s'),
                    'file_size' => $version->file_size,
                    'relationship_description' => $link->relationship_description,
                ];
            })
            ->toArray();
    }
}