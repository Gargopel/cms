<?php

namespace Plugins\Pages\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Plugins\Pages\Enums\PageStatus;
use Plugins\Pages\Http\Requests\StorePageRequest;
use Plugins\Pages\Http\Requests\UpdatePageRequest;
use Plugins\Pages\Models\Page;

class PageController extends Controller
{
    public function index(): View
    {
        return view('pages::admin.index', [
            'pageTitle' => 'Pages',
            'pageSubtitle' => 'Plugin oficial para paginas publicas simples, com governanca minima e renderizacao segura.',
            'pages' => Page::query()
                ->orderByDesc('updated_at')
                ->paginate(20),
            'summary' => [
                'total' => Page::query()->count(),
                'draft' => Page::query()->where('status', PageStatus::Draft->value)->count(),
                'published' => Page::query()->where('status', PageStatus::Published->value)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('pages::admin.form', [
            'pageTitle' => 'Create Page',
            'pageSubtitle' => 'Nova pagina simples do plugin oficial Pages.',
            'pageRecord' => new Page([
                'status' => PageStatus::Draft,
            ]),
            'submitRoute' => route('plugins.pages.admin.store'),
            'submitMethod' => 'POST',
        ]);
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        Page::query()->create($request->validated());

        return redirect()
            ->to($this->adminPagesPath())
            ->with('status', 'Page created successfully.');
    }

    public function edit(Page $page): View
    {
        return view('pages::admin.form', [
            'pageTitle' => 'Edit Page',
            'pageSubtitle' => 'Atualize titulo, slug, conteudo e status da pagina.',
            'pageRecord' => $page,
            'submitRoute' => route('plugins.pages.admin.update', $page),
            'submitMethod' => 'PUT',
        ]);
    }

    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $page->update($request->validated());

        return redirect()
            ->to($this->adminPagesPath())
            ->with('status', 'Page updated successfully.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()
            ->to($this->adminPagesPath())
            ->with('status', 'Page deleted successfully.');
    }

    protected function adminPagesPath(): string
    {
        return '/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/pages';
    }
}
