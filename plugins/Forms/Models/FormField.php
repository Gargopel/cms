<?php

namespace Plugins\Forms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Plugins\Forms\Enums\FormFieldType;

class FormField extends Model
{
    protected $table = 'plugin_forms_fields';

    protected $fillable = [
        'form_id',
        'label',
        'name',
        'type',
        'placeholder',
        'help_text',
        'options',
        'is_required',
        'sort_order',
    ];

    protected $casts = [
        'type' => FormFieldType::class,
        'options' => 'array',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function submissionValues(): HasMany
    {
        return $this->hasMany(FormSubmissionValue::class, 'form_field_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return array<int, string>
     */
    public function optionValues(): array
    {
        return collect($this->options ?? [])
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))
            ->values()
            ->all();
    }
}
