<?php

namespace Plugins\Pages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Plugins\Pages\Enums\PageStatus;
use Plugins\Pages\Enums\PagesPermission;
use Plugins\Pages\Models\Page;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(PagesPermission::EditPages->value);
    }

    protected function prepareForValidation(): void
    {
        $slug = trim((string) $this->input('slug', ''));
        $title = trim((string) $this->input('title', ''));

        if ($slug === '' && $title !== '') {
            $slug = Str::slug($title);
        }

        /** @var Page|null $page */
        $page = $this->route('page');
        $status = $this->input('status', $page?->status?->value ?? PageStatus::Draft->value);

        if (! ($this->user()?->can(PagesPermission::PublishPages->value) ?? false)) {
            $status = $page?->status?->value ?? PageStatus::Draft->value;
        }

        $this->merge([
            'slug' => $slug,
            'status' => $status,
        ]);
    }

    public function rules(): array
    {
        /** @var Page|null $page */
        $page = $this->route('page');

        return [
            'title' => ['required', 'string', 'max:180'],
            'slug' => [
                'required',
                'string',
                'max:180',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('plugin_pages_pages', 'slug')->ignore($page?->getKey()),
            ],
            'content' => ['required', 'string'],
            'status' => ['required', Rule::in(PageStatus::values())],
        ];
    }
}
