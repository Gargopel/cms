<?php

namespace Plugins\Blog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Plugins\Blog\Models\Tag;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Tag $tag */
        $tag = $this->route('tag');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('plugin_blog_tags', 'slug')->ignore($tag->getKey()),
            ],
            'description' => ['nullable', 'string'],
        ];
    }
}
