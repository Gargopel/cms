<?php

namespace Plugins\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Plugins\Forms\Enums\FormFieldType;
use Plugins\Forms\Enums\FormsPermission;
use Plugins\Forms\Models\Form;
use Plugins\Forms\Models\FormField;

class UpdateFormFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(FormsPermission::EditForms->value);
    }

    protected function prepareForValidation(): void
    {
        $label = trim((string) $this->input('label', ''));
        $name = trim((string) $this->input('name', ''));

        if ($name === '' && $label !== '') {
            $name = Str::snake(Str::lower($label));
        }

        $options = collect(preg_split('/\r\n|\r|\n/', (string) $this->input('options_text', '')) ?: [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();

        $this->merge([
            'name' => $name,
            'options' => $options,
            'is_required' => $this->boolean('is_required'),
            'sort_order' => $this->input('sort_order', 10),
        ]);
    }

    public function rules(): array
    {
        /** @var Form $form */
        $form = $this->route('form');
        /** @var FormField $field */
        $field = $this->route('field');

        return [
            'label' => ['required', 'string', 'max:180'],
            'name' => [
                'required',
                'string',
                'max:180',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('plugin_forms_fields', 'name')
                    ->where('form_id', $form->getKey())
                    ->ignore($field->getKey()),
            ],
            'type' => ['required', Rule::in(FormFieldType::values())],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:1000'],
            'options' => ['nullable', 'array'],
            'options.*' => ['string', 'max:180'],
            'is_required' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('type') !== FormFieldType::Select->value) {
                return;
            }

            if (count($this->input('options', [])) === 0) {
                $validator->errors()->add('options_text', 'Select fields require at least one option.');
            }
        });
    }
}
