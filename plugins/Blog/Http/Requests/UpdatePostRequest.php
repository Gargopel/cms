<?php

namespace Plugins\Blog\Http\Requests;

use App\Core\Media\Rules\ValidFeaturedImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Plugins\Blog\Enums\BlogPermission;
use Plugins\Blog\Enums\PostStatus;
use Plugins\Blog\Models\Post;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(BlogPermission::EditPosts->value);
    }

    protected function prepareForValidation(): void
    {
        $slug = trim((string) $this->input('slug', ''));
        $title = trim((string) $this->input('title', ''));

        if ($slug === '' && $title !== '') {
            $slug = Str::slug($title);
        }

        /** @var Post|null $post */
        $post = $this->route('post');
        $status = $this->input('status', $post?->status?->value ?? PostStatus::Draft->value);
        $publishedAt = $post?->published_at;

        if (! ($this->user()?->can(BlogPermission::PublishPosts->value) ?? false)) {
            $status = $post?->status?->value ?? PostStatus::Draft->value;
        }

        if ($status === PostStatus::Published->value) {
            $publishedAt ??= Carbon::now();
        } else {
            $publishedAt = null;
        }

        $this->merge([
            'slug' => $slug,
            'status' => $status,
            'published_at' => $publishedAt?->toDateTimeString(),
        ]);
    }

    public function rules(): array
    {
        /** @var Post|null $post */
        $post = $this->route('post');

        return [
            'title' => ['required', 'string', 'max:180'],
            'slug' => [
                'required',
                'string',
                'max:180',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('plugin_blog_posts', 'slug')->ignore($post?->getKey()),
            ],
            'excerpt' => ['required', 'string', 'max:320'],
            'content' => ['required', 'string'],
            'status' => ['required', Rule::in(PostStatus::values())],
            'published_at' => ['nullable', 'date'],
            'featured_image_id' => ['nullable', 'integer', new ValidFeaturedImage],
            'category_id' => ['nullable', 'integer', Rule::exists('plugin_blog_categories', 'id')],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', Rule::exists('plugin_blog_tags', 'id')],
        ];
    }
}
