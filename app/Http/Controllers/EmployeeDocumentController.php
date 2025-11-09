<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
// use App\Jobs\IndexDocumentContentJob; // TODO: Uncomment when implementing document search
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\File;

class EmployeeDocumentController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, Employee $employee)
    {
        $this->authorize('create', [EmployeeDocument::class, $employee]);

        $request->validate([
            'document_type' => 'required|string|in:id_card,passport,birth_certificate,diploma,license,contract,other',
            'document' => [
                'required',
                'file',
                File::types(['pdf', 'jpg', 'jpeg', 'png'])
                    ->max(5 * 1024) // 5MB
                    ->min(1) // 1KB minimum
            ],
        ]);

        $uploadedFile = $request->file('document');
        
        // Additional security checks
        $this->validateFileContent($uploadedFile);
        
        // Generate secure filename
        $originalName = $uploadedFile->getClientOriginalName();
        $extension = $uploadedFile->getClientOriginalExtension();
        $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '', pathinfo($originalName, PATHINFO_FILENAME));
        $secureFileName = $sanitizedName . '_' . time() . '.' . $extension;
        
        // Store file with secure name
        $path = $uploadedFile->storeAs(
            'private/employee_documents/' . $employee->id,
            $secureFileName,
            's3'
        );

        $document = $employee->documents()->create([
            'document_type' => $request->document_type,
            'file_name' => $originalName,
            'file_path' => $path,
            'storage_disk' => 's3',
            'uploaded_by' => auth()->id(),
            'uploaded_at' => now(),
            'file_size' => $uploadedFile->getSize(),
            'mime_type' => $uploadedFile->getMimeType(),
        ]);

        Log::info('Document uploaded', [
            'employee_id' => $employee->id,
            'document_id' => $document->id,
            'uploaded_by' => auth()->id(),
            'file_size' => $uploadedFile->getSize(),
        ]);

        // TODO: Implement document content indexing when needed
        // IndexDocumentContentJob::dispatch($document);

        return back()->with('success', 'Document uploaded successfully.');
    }

    private function validateFileContent($file)
    {
        $allowedMimeTypes = [
            'application/pdf',
            'image/jpeg',
            'image/jpg', 
            'image/png'
        ];

        $detectedMimeType = mime_content_type($file->getRealPath());
        
        if (!in_array($detectedMimeType, $allowedMimeTypes)) {
            throw new \InvalidArgumentException('Invalid file type detected.');
        }

        // Check for malicious content in file headers
        $fileContent = file_get_contents($file->getRealPath(), false, null, 0, 1024);
        $maliciousPatterns = ['<?php', '<script', 'javascript:', 'eval(', 'exec('];
        
        foreach ($maliciousPatterns as $pattern) {
            if (stripos($fileContent, $pattern) !== false) {
                Log::warning('Malicious file upload attempt', [
                    'file_name' => $file->getClientOriginalName(),
                    'uploaded_by' => auth()->id(),
                    'detected_pattern' => $pattern
                ]);
                throw new \InvalidArgumentException('File contains suspicious content.');
            }
        }
    }

    public function show(EmployeeDocument $document)
    {
        $this->authorize('view', $document);

        return Storage::download($document->file_path, $document->file_name);
    }

    public function destroy(EmployeeDocument $document)
    {
        $this->authorize('delete', $document);

        Storage::delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document deleted successfully.');
    }
}