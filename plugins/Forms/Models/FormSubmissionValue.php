<?php

namespace Plugins\Forms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmissionValue extends Model
{
    protected $table = 'plugin_forms_submission_values';

    protected $fillable = [
        'submission_id',
        'form_field_id',
        'field_name',
        'field_label',
        'value',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'submission_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'form_field_id');
    }
}
