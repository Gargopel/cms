<?php

namespace App\Core\Media\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    protected $fillable = [
        'disk',
        'original_name',
        'stored_name',
        'path',
        'mime_type',
        'size_bytes',
        'extension',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'uploaded_by' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): ?string
    {
        try {
            return Storage::disk($this->disk)->url($this->path);
        } catch (\Throwable) {
            return null;
        }
    }

    public function humanSize(): string
    {
        $size = max(0, (int) $this->size_bytes);

        if ($size >= 1024 * 1024) {
            return number_format($size / (1024 * 1024), 2).' MB';
        }

        if ($size >= 1024) {
            return number_format($size / 1024, 2).' KB';
        }

        return $size.' B';
    }

    public function isImage(): bool
    {
        return is_string($this->mime_type) && str_starts_with($this->mime_type, 'image/');
    }
}
