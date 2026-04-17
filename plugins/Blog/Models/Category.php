<?php

namespace Plugins\Blog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'plugin_blog_categories';

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

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'category_id');
    }
}
