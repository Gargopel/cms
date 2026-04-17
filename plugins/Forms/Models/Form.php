<?php

namespace Plugins\Forms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Plugins\Forms\Enums\FormStatus;

class Form extends Model
{
    protected $table = 'plugin_forms_forms';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'success_message',
        'status',
    ];

    protected $casts = [
        'status' => FormStatus::class,
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class, 'form_id')->orderBy('sort_order')->orderBy('id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'form_id')->orderByDesc('submitted_at')->orderByDesc('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', FormStatus::Published->value);
    }
}
