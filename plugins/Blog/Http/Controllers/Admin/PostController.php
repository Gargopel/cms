<?php

namespace Plugins\Blog\Http\Controllers\Admin;

use App\Core\Media\Models\MediaAsset;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Plugins\Blog\Enums\PostStatus;
use Plugins\Blog\Http\Requests\StorePostRequest;
use Plugins\Blog\Http\Requests\UpdatePostRequest;
use Plugins\Blog\Models\Category;
use Plugins\Blog\Models\Post;
use Plugins\Blog\Models\Tag;

class PostController extends Controller
{
    public function index(): View
    {
        return view('blog::admin.index', [
            'pageTitle' => 'Blog',
            'pageSubtitle' => 'Plugin oficial editorial para posts simples, publicados com controle operacional e RBAC real.',
            'posts' => Post::query()
                ->with(['category', 'tags'])
                ->orderByDesc('published_at')
                ->orderByDesc('updated_at')
                ->paginate(20),
            'summary' => [
                'total' => Post::query()->count(),
                'draft' => Post::query()->where('status', PostStatus::Draft->value)->count(),
                'published' => Post::query()->where('status', PostStatus::Published->value)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('blog::admin.form', [
            'pageTitle' => 'Create Post',
            'pageSubtitle' => 'Novo post editorial do plugin oficial Blog.',
            'postRecord' => new Post([
                'status' => PostStatus::Draft,
            ]),
            'submitRoute' => route('plugins.blog.admin.store'),
            'submitMethod' => 'POST',
            'featuredImageOptions' => $this->featuredImageOptions(),
            'categoryOptions' => $this->categoryOptions(),
            'tagOptions' => $this->tagOptions(),
        ]);
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $post = Post::query()->create($validated);
        $post->tags()->sync($tagIds);

        return redirect()
            ->to($this->adminPostsPath())
            ->with('status', 'Post created successfully.');
    }

    public function edit(Post $post): View
    {
        $post->loadMissing('tags');

        return view('blog::admin.form', [
            'pageTitle' => 'Edit Post',
            'pageSubtitle' => 'Atualize titulo, slug, resumo, conteudo e status editorial do post.',
            'postRecord' => $post,
            'submitRoute' => route('plugins.blog.admin.update', $post),
            'submitMethod' => 'PUT',
            'featuredImageOptions' => $this->featuredImageOptions(),
            'categoryOptions' => $this->categoryOptions(),
            'tagOptions' => $this->tagOptions(),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $validated = $request->validated();
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $post->update($validated);
        $post->tags()->sync($tagIds);

        return redirect()
            ->to($this->adminPostsPath())
            ->with('status', 'Post updated successfully.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return redirect()
            ->to($this->adminPostsPath())
            ->with('status', 'Post deleted successfully.');
    }

    protected function adminPostsPath(): string
    {
        return '/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/blog/posts';
    }

    protected function featuredImageOptions()
    {
        return MediaAsset::query()
            ->where('mime_type', 'like', 'image/%')
            ->latest('created_at')
            ->limit(100)
            ->get();
    }

    protected function categoryOptions()
    {
        return Category::query()
            ->orderBy('name')
            ->get();
    }

    protected function tagOptions()
    {
        return Tag::query()
            ->orderBy('name')
            ->get();
    }
}
