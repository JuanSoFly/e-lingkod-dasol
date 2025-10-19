<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get employee documents
     */
    public function index(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;

        $documents = EmployeeDocument::where('employee_id', $employee->id)
            ->when($request->input('category'), function ($query, $category) {
                $query->where('category', $category);
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'document_type' => $doc->document_type,
                    'category' => $doc->category,
                    'filename' => $doc->filename,
                    'file_path' => $doc->file_path,
                    'file_size' => $doc->file_size,
                    'upload_date' => $doc->created_at->format('Y-m-d H:i:s'),
                    'description' => $doc->description,
                    'is_verified' => $doc->is_verified,
                    'verified_by' => $doc->verified_by,
                    'verified_at' => $doc->verified_at?->format('Y-m-d H:i:s'),
                ];
            });

        $categories = EmployeeDocument::where('employee_id', $employee->id)
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();

        return response()->json([
            'documents' => $documents,
            'categories' => $categories,
            'storage_used' => $documents->sum('file_size'),
            'storage_limit' => 50 * 1024 * 1024, // 50MB limit
        ]);
    }

    /**
     * Upload a new document
     */
    public function store(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;

        $validated = $request->validate([
            'document_type' => ['required', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'], // 10MB max
        ]);

        try {
            // Check storage limit
            $currentStorage = EmployeeDocument::where('employee_id', $employee->id)
                ->sum('file_size');
            $newFileSize = $request->file('file')->getSize();
            $storageLimit = 50 * 1024 * 1024; // 50MB

            if ($currentStorage + $newFileSize > $storageLimit) {
                return response()->json([
                    'message' => 'Storage limit exceeded. Maximum storage is 50MB.',
                ], 422);
            }

            $file = $request->file('file');
            $filename = $this->generateUniqueFilename($file);
            $filePath = $this->storeFile($file, $filename, $employee->id);

            // Create document record
            $document = EmployeeDocument::create([
                'employee_id' => $employee->id,
                'document_type' => $validated['document_type'],
                'category' => $validated['category'],
                'filename' => $filename,
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_size' => $newFileSize,
                'mime_type' => $file->getMimeType(),
                'description' => $validated['description'],
                'is_verified' => false,
                'uploaded_by' => auth()->user()->id,
            ]);

            return response()->json([
                'message' => 'Document uploaded successfully',
                'document' => [
                    'id' => $document->id,
                    'document_type' => $document->document_type,
                    'category' => $document->category,
                    'filename' => $document->filename,
                    'file_path' => $document->file_path,
                    'file_size' => $document->file_size,
                    'upload_date' => $document->created_at->format('Y-m-d H:i:s'),
                    'description' => $document->description,
                    'is_verified' => $document->is_verified,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload document',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download a document
     */
    public function download(EmployeeDocument $document): JsonResponse
    {
        $employee = auth()->user()->employee;

        // Ensure employee can only download their own documents
        if ($document->employee_id !== $employee->id) {
            return response()->json([
                'message' => 'Unauthorized action',
            ], 403);
        }

        try {
            if (!Storage::disk('local')->exists($document->file_path)) {
                return response()->json([
                    'message' => 'File not found',
                ], 404);
            }

            return response()->json([
                'download_url' => route('employee-portal.documents.download-file', $document->id),
                'filename' => $document->original_filename,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve document',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download file (actual file download)
     */
    public function downloadFile(EmployeeDocument $document)
    {
        $employee = auth()->user()->employee;

        // Ensure employee can only download their own documents
        if ($document->employee_id !== $employee->id) {
            abort(403);
        }

        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($document->file_path, $document->original_filename);
    }

    /**
     * Delete a document
     */
    public function destroy(EmployeeDocument $document): JsonResponse
    {
        $employee = auth()->user()->employee;

        // Ensure employee can only delete their own documents
        if ($document->employee_id !== $employee->id) {
            return response()->json([
                'message' => 'Unauthorized action',
            ], 403);
        }

        try {
            // Delete file from storage
            if (Storage::disk('local')->exists($document->file_path)) {
                Storage::disk('local')->delete($document->file_path);
            }

            // Delete database record
            $document->delete();

            return response()->json([
                'message' => 'Document deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete document',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update document information
     */
    public function update(Request $request, EmployeeDocument $document): JsonResponse
    {
        $employee = auth()->user()->employee;

        // Ensure employee can only update their own documents
        if ($document->employee_id !== $employee->id) {
            return response()->json([
                'message' => 'Unauthorized action',
            ], 403);
        }

        $validated = $request->validate([
            'document_type' => ['required', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $document->update($validated);

            return response()->json([
                'message' => 'Document updated successfully',
                'document' => [
                    'id' => $document->id,
                    'document_type' => $document->document_type,
                    'category' => $document->category,
                    'filename' => $document->filename,
                    'description' => $document->description,
                    'updated_at' => $document->updated_at->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update document',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFilename($file): string
    {
        $extension = $file->getClientOriginalExtension();
        $basename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $timestamp = time();

        return "{$basename}_{$timestamp}.{$extension}";
    }

    /**
     * Store file in storage
     */
    private function storeFile($file, string $filename, int $employeeId): string
    {
        $directory = "documents/employees/{$employeeId}";
        return $file->storeAs($directory, $filename, 'local');
    }
}