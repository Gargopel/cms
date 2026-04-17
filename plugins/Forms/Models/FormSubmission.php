<?php

namespace Plugins\Forms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSubmission extends Model
{
    protected $table = 'plugin_forms_submissions';

    protected $fillable = [
        'form_id',
        'submitted_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(FormSubmissionValue::class, 'submission_id')->orderBy('id');
    }
}
