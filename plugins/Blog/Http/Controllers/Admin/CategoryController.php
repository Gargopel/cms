<?php

namespace Plugins\Blog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Plugins\Blog\Http\Requests\StoreCategoryRequest;
use Plugins\Blog\Http\Requests\UpdateCategoryRequest;
use Plugins\Blog\Models\Category;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('blog::admin.categories.index', [
            'pageTitle' => 'Blog Categories',
            'pageSubtitle' => 'Categorias editoriais simples para organizar posts publicados no plugin oficial Blog.',
            'categories' => Category::query()
                ->withCount('posts')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('blog::admin.categories.form', [
            'pageTitle' => 'Create Category',
            'pageSubtitle' => 'Nova categoria editorial simples do plugin Blog.',
            'categoryRecord' => new Category(),
            'submitRoute' => $this->adminCategoriesPath(),
            'submitMethod' => 'POST',
            'indexPath' => $this->adminCategoriesPath(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::query()->create($request->validated());

        return redirect()
            ->to($this->adminCategoriesPath())
            ->with('status', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        return view('blog::admin.categories.form', [
            'pageTitle' => 'Edit Category',
            'pageSubtitle' => 'Atualize nome, slug e descricao da categoria.',
            'categoryRecord' => $category,
            'submitRoute' => $this->adminCategoriesPath().'/'.$category->getKey(),
            'submitMethod' => 'PUT',
            'indexPath' => $this->adminCategoriesPath(),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()
            ->to($this->adminCategoriesPath())
            ->with('status', 'Category updated successfully.');
    }

    protected function adminCategoriesPath(): string
    {
        return '/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/blog/categories';
    }
}
