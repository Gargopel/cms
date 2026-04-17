<?php

namespace App\Core\Media;

use App\Core\Media\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaManager
{
    public function disk(): string
    {
        return (string) config('platform.media.disk', 'public');
    }

    public function directory(): string
    {
        return trim((string) config('platform.media.directory', 'media'), '/');
    }

    /**
     * @return array<int, string>
     */
    public function allowedMimeTypes(): array
    {
        return array_values(array_filter(
            (array) config('platform.media.allowed_mime_types', []),
            static fn (mixed $value): bool => is_string($value) && trim($value) !== '',
        ));
    }

    /**
     * @return array<int, string>
     */
    public function allowedExtensions(): array
    {
        return array_values(array_map(
            static fn (string $extension): string => strtolower(trim($extension)),
            array_filter(
                (array) config('platform.media.allowed_extensions', []),
                static fn (mixed $value): bool => is_string($value) && trim($value) !== '',
            ),
        ));
    }

    public function maxUploadKilobytes(): int
    {
        return (int) config('platform.media.max_upload_kilobytes', 5120);
    }

    public function store(UploadedFile $file, ?int $uploadedBy = null): MediaUploadResult
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $originalName = trim((string) $file->getClientOriginalName());
        $mimeType = (string) $file->getClientMimeType();

        if (! in_array($extension, $this->allowedExtensions(), true)) {
            return new MediaUploadResult(
                success: false,
                message: 'This file extension is not allowed for the current media policy.',
            );
        }

        if (! in_array($mimeType, $this->allowedMimeTypes(), true)) {
            return new MediaUploadResult(
                success: false,
                message: 'This file mime type is not allowed for the current media policy.',
            );
        }

        $storedName = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
        $relativeDirectory = $this->directory().'/'.now()->format('Y/m');
        $path = trim($relativeDirectory.'/'.$storedName, '/');

        Storage::disk($this->disk())->putFileAs(
            $relativeDirectory,
            $file,
            $storedName,
            ['visibility' => 'public'],
        );

        /** @var MediaAsset $asset */
        $asset = MediaAsset::query()->create([
            'disk' => $this->disk(),
            'original_name' => $originalName !== '' ? $originalName : $storedName,
            'stored_name' => $storedName,
            'path' => $path,
            'mime_type' => $mimeType,
            'size_bytes' => (int) $file->getSize(),
            'extension' => $extension,
            'uploaded_by' => $uploadedBy,
        ]);

        return new MediaUploadResult(
            success: true,
            message: 'Media asset uploaded successfully.',
            asset: $asset,
        );
    }
}
