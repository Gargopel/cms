<?php

namespace Plugins\Forms\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Plugins\Forms\Models\Form;
use Plugins\Forms\Models\FormSubmission;

class FormSubmissionNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Form $form,
        public readonly FormSubmission $submission,
    ) {
    }

    public function build(): static
    {
        return $this->subject('New submission: '.$this->form->title)
            ->view('forms::mail.submission-notification');
    }
}
