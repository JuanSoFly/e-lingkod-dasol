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

    private function resolveMime(EmployeeDocument $document, ?FilesystemAdapter $disk = null, ?string $path = null): string
    {
        if (!empty($document->mime_type)) {
            return $document->mime_type;
        }

        $disk ??= $this->disk($document);
        $path ??= $this->resolvePath($document);

        if ($path) {
            try {
                $mime = $disk->mimeType($path);

                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            } catch (\Throwable $e) {
                // Swallow and allow fallback resolution below.
            }
        }

        $extension = strtolower(pathinfo($document->file_name ?? $document->file_path ?? '', PATHINFO_EXTENSION));

        if ($extension !== '') {
            $fallbackMime = match ($extension) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'bmp' => 'image/bmp',
                'webp' => 'image/webp',
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                default => null,
            };

            if ($fallbackMime) {
                return $fallbackMime;
            }
        }

        return 'application/octet-stream';
    }

    private function disk(EmployeeDocument $document): FilesystemAdapter
    {
        $diskName = $document->storageDiskName() ?: config('filesystems.default');

        return Storage::disk($diskName);
    }

    private function pathOrFail(EmployeeDocument $document, FilesystemAdapter $disk): string
    {
        $path = $this->resolvePath($document);

        if (!$path || !$disk->exists($path)) {
            throw new FileNotFoundException('Document file is missing.');
        }

        return $path;
    }

    private function resolvePath(EmployeeDocument $document): ?string
    {
        return $document->usesLocalDisk()
            ? $document->resolvedStoragePath()
            : ($document->file_path ?: null);
    }
}
