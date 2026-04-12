<?php

namespace Plugins\Pages\Http\Controllers;

use App\Core\Themes\ThemeViewResolver;
use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Plugins\Pages\Models\Page;

class PublicPageController extends Controller
{
    public function __invoke(string $slug, ThemeViewResolver $themes): View
    {
        $page = Page::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view($themes->resolve('plugins.pages.show', 'pages::front.show'), [
            'page' => $page,
        ]);
    }
}
