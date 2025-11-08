<?php

namespace App\Services;

use App\Models\EmployeeDocument;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentAccessService
{
    private const PREVIEWABLE_MIMES = [
        'application/pdf',
    ];

    public function fileExists(EmployeeDocument $document): bool
    {
        $disk = $this->disk($document);

        if ($document->usesLocalDisk()) {
            return (bool) $document->resolvedStoragePath();
        }

        return $document->file_path ? $disk->exists($document->file_path) : false;
    }

    /**
     * @throws FileNotFoundException
     */
    public function download(EmployeeDocument $document): StreamedResponse
    {
        $disk = $this->disk($document);
        $path = $this->pathOrFail($document, $disk);

        return $disk->download(
            $path,
            $document->display_file_name,
            $this->buildHeaders($document, $disk, $path, 'attachment')
        );
    }

    /**
     * @return BinaryFileResponse|StreamedResponse
     * @throws FileNotFoundException
     */
    public function preview(EmployeeDocument $document)
    {
        if (!$this->canPreview($document)) {
            return $this->download($document);
        }

        $disk = $this->disk($document);
        $path = $this->pathOrFail($document, $disk);

        return $disk->response(
            $path,
            $document->display_file_name,
            $this->buildHeaders($document, $disk, $path, 'inline'),
            'inline'
        );
    }

    public function canPreview(EmployeeDocument $document): bool
    {
        $mime = $this->resolveMime($document);

        return str_starts_with($mime, 'image/') || in_array($mime, self::PREVIEWABLE_MIMES, true);
    }

    private function buildHeaders(EmployeeDocument $document, FilesystemAdapter $disk, string $path, string $disposition): array
    {
        return [
            'Content-Type' => $this->resolveMime($document, $disk, $path),
            'Content-Disposition' => sprintf('%s; filename="%s"', $disposition, addslashes($document->display_file_name)),
        ];
    }

    private function resolveMime(EmployeeDocument $document, FilesystemAdapter $disk, string $path): string
    {
        if (!empty($document->mime_type)) {
            return $document->mime_type;
        }

        try {
            return $disk->mimeType($path);
        } catch (\Throwable $e) {
            return 'application/octet-stream';
        }
    }

    private function disk(EmployeeDocument $document): FilesystemAdapter
    {
        $diskName = $document->storageDiskName() ?: config('filesystems.default');

        return Storage::disk($diskName);
    }

    private function pathOrFail(EmployeeDocument $document, FilesystemAdapter $disk): string
    {
        $path = $document->usesLocalDisk()
            ? $document->resolvedStoragePath()
            : $document->file_path;

        if (!$path || !$disk->exists($path)) {
            throw new FileNotFoundException('Document file is missing.');
        }

        return $path;
    }
}
