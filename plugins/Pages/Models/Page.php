<?php

namespace Plugins\Pages\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Plugins\Pages\Enums\PageStatus;

class Page extends Model
{
    protected $table = 'plugin_pages_pages';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PageStatus::Published->value);
    }
}
