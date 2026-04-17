<?php

namespace Plugins\Blog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Plugins\Blog\Http\Requests\StoreTagRequest;
use Plugins\Blog\Http\Requests\UpdateTagRequest;
use Plugins\Blog\Models\Tag;

class TagController extends Controller
{
    public function index(): View
    {
        return view('blog::admin.tags.index', [
            'pageTitle' => 'Blog Tags',
            'pageSubtitle' => 'Tags editoriais simples para agrupar posts por assunto sem virar taxonomia generica.',
            'tags' => Tag::query()
                ->withCount('posts')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('blog::admin.tags.form', [
            'pageTitle' => 'Create Tag',
            'pageSubtitle' => 'Nova tag editorial simples do plugin Blog.',
            'tagRecord' => new Tag(),
            'submitRoute' => $this->adminTagsPath(),
            'submitMethod' => 'POST',
            'indexPath' => $this->adminTagsPath(),
        ]);
    }

    public function store(StoreTagRequest $request): RedirectResponse
    {
        Tag::query()->create($request->validated());

        return redirect()
            ->to($this->adminTagsPath())
            ->with('status', 'Tag created successfully.');
    }

    public function edit(Tag $tag): View
    {
        return view('blog::admin.tags.form', [
            'pageTitle' => 'Edit Tag',
            'pageSubtitle' => 'Atualize nome, slug e descricao da tag editorial.',
            'tagRecord' => $tag,
            'submitRoute' => $this->adminTagsPath().'/'.$tag->getKey(),
            'submitMethod' => 'PUT',
            'indexPath' => $this->adminTagsPath(),
        ]);
    }

    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $tag->update($request->validated());

        return redirect()
            ->to($this->adminTagsPath())
            ->with('status', 'Tag updated successfully.');
    }

    protected function adminTagsPath(): string
    {
        return '/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/blog/tags';
    }
}
