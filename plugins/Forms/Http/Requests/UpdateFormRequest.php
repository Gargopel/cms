<?php

namespace Plugins\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Plugins\Forms\Enums\FormStatus;
use Plugins\Forms\Enums\FormsPermission;
use Plugins\Forms\Models\Form;

class UpdateFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(FormsPermission::EditForms->value);
    }

    protected function prepareForValidation(): void
    {
        $slug = trim((string) $this->input('slug', ''));
        $title = trim((string) $this->input('title', ''));

        if ($slug === '' && $title !== '') {
            $slug = Str::slug($title);
        }

        /** @var Form|null $form */
        $form = $this->route('form');
        $status = $this->input('status', $form?->status?->value ?? FormStatus::Draft->value);

        if (! ($this->user()?->can(FormsPermission::PublishForms->value) ?? false)) {
            $status = $form?->status?->value ?? FormStatus::Draft->value;
        }

        $this->merge([
            'slug' => $slug,
            'status' => $status,
        ]);
    }

    public function rules(): array
    {
        /** @var Form $form */
        $form = $this->route('form');

        return [
            'title' => ['required', 'string', 'max:180'],
            'slug' => [
                'required',
                'string',
                'max:180',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('plugin_forms_forms', 'slug')->ignore($form->getKey()),
            ],
            'description' => ['nullable', 'string'],
            'success_message' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(FormStatus::values())],
        ];
    }
}
