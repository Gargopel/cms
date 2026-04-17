<?php

namespace Plugins\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Plugins\Forms\Enums\FormStatus;
use Plugins\Forms\Enums\FormsPermission;

class StoreFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(FormsPermission::CreateForms->value);
    }

    protected function prepareForValidation(): void
    {
        $slug = trim((string) $this->input('slug', ''));
        $title = trim((string) $this->input('title', ''));

        if ($slug === '' && $title !== '') {
            $slug = Str::slug($title);
        }

        $status = $this->input('status', FormStatus::Draft->value);

        if (! ($this->user()?->can(FormsPermission::PublishForms->value) ?? false)) {
            $status = FormStatus::Draft->value;
        }

        $this->merge([
            'slug' => $slug,
            'status' => $status,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'slug' => [
                'required',
                'string',
                'max:180',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('plugin_forms_forms', 'slug'),
            ],
            'description' => ['nullable', 'string'],
            'success_message' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(FormStatus::values())],
        ];
    }
}
