<?php

namespace Plugins\Forms\Support;

use Plugins\Forms\Models\FormSubmission;

class FormSubmissionResult
{
    public function __construct(
        public readonly FormSubmission $submission,
        public readonly string $successMessage,
        public readonly ?string $redirectUrl = null,
        public readonly bool $notificationSent = false,
    ) {
    }
}
