<?php

namespace Plugins\Forms\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Plugins\Forms\Enums\FormFieldType;
use Plugins\Forms\Models\Form;
use Plugins\Forms\Models\FormField;
use Plugins\Forms\Models\FormSubmission;

class FormSubmissionService
{
    public function submit(Form $form, Request $request): FormSubmission
    {
        $fields = $form->fields()->ordered()->get();

        $validated = Validator::make(
            $request->all(),
            $this->rulesFor($fields->all()),
            [],
            $this->attributesFor($fields->all()),
        )->validate();

        return DB::transaction(function () use ($form, $fields, $validated, $request): FormSubmission {
            $submission = $form->submissions()->create([
                'submitted_at' => Carbon::now(),
                'ip_address' => $request->ip(),
                'user_agent' => $this->trimUserAgent($request->userAgent()),
            ]);

            foreach ($fields as $field) {
                $submission->values()->create([
                    'form_field_id' => $field->getKey(),
                    'field_name' => $field->name,
                    'field_label' => $field->label,
                    'value' => $this->normalizeValue($field, $validated),
                ]);
            }

            return $submission->load('values.field');
        });
    }

    /**
     * @param  array<int, FormField>  $fields
     * @return array<string, array<int, mixed>>
     */
    protected function rulesFor(array $fields): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $rules[$field->name] = $field->type->submissionRules($field->is_required, $field->optionValues());
        }

        return $rules;
    }

    /**
     * @param  array<int, FormField>  $fields
     * @return array<string, string>
     */
    protected function attributesFor(array $fields): array
    {
        $attributes = [];

        foreach ($fields as $field) {
            $attributes[$field->name] = $field->label;
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function normalizeValue(FormField $field, array $validated): ?string
    {
        if ($field->type === FormFieldType::Checkbox) {
            $rawValue = $validated[$field->name] ?? null;

            return filter_var($rawValue, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }

        $rawValue = $validated[$field->name] ?? null;

        if (! is_string($rawValue)) {
            return null;
        }

        $value = trim($rawValue);

        return $value === '' ? null : $value;
    }

    protected function trimUserAgent(?string $userAgent): ?string
    {
        if (! is_string($userAgent) || trim($userAgent) === '') {
            return null;
        }

        return mb_substr(trim($userAgent), 0, 500);
    }
}
