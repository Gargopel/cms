<?php

namespace Plugins\Pages\Models;

use App\Core\Media\Models\MediaAsset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Plugins\Pages\Enums\PageStatus;

class Page extends Model
{
    protected $table = 'plugin_pages_pages';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'status',
        'featured_image_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'featured_image_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PageStatus::Published->value);
    }
}
