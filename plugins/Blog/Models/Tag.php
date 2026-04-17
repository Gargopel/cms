<?php

namespace Plugins\Blog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $table = 'plugin_blog_tags';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(
            Post::class,
            'plugin_blog_post_tag',
            'tag_id',
            'post_id',
        );
    }
}
