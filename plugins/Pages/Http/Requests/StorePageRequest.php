<?php

namespace Plugins\Pages\Http\Requests;

use App\Core\Media\Rules\ValidFeaturedImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Plugins\Pages\Enums\PageStatus;
use Plugins\Pages\Enums\PagesPermission;

class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(PagesPermission::CreatePages->value);
    }

    protected function prepareForValidation(): void
    {
        $slug = trim((string) $this->input('slug', ''));
        $title = trim((string) $this->input('title', ''));

        if ($slug === '' && $title !== '') {
            $slug = Str::slug($title);
        }

        $status = $this->input('status', PageStatus::Draft->value);

        if (! ($this->user()?->can(PagesPermission::PublishPages->value) ?? false)) {
            $status = PageStatus::Draft->value;
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
                Rule::unique('plugin_pages_pages', 'slug'),
            ],
            'content' => ['required', 'string'],
            'status' => ['required', Rule::in(PageStatus::values())],
            'featured_image_id' => ['nullable', 'integer', new ValidFeaturedImage],
        ];
    }
}
