<?php

namespace Plugins\Blog\Models;

use App\Core\Media\Models\MediaAsset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Plugins\Blog\Enums\PostStatus;

class Post extends Model
{
    protected $table = 'plugin_blog_posts';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'status',
        'published_at',
        'featured_image_id',
        'category_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'featured_image_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            Tag::class,
            'plugin_blog_post_tag',
            'post_id',
            'tag_id',
        )->withTimestamps();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Published->value);
    }
}
