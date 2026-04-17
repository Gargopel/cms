<?php

namespace Plugins\Pages\Http\Controllers;

use App\Core\Themes\ThemeViewResolver;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Plugins\Pages\Models\Page;

class PublicPageController extends Controller
{
    public function __invoke(string $slug, ThemeViewResolver $themes): View
    {
        $page = Page::query()
            ->with('featuredImage')
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $seo = $this->resolveSeo([
            'title' => $page->title,
            'description' => Str::limit(trim(strip_tags($page->content)), 160, ''),
            'canonical' => url('/pages/'.$page->slug),
            'og_type' => 'website',
            'og_image' => $page->featuredImage?->url(),
        ]);

        return view($themes->resolve('plugins.pages.show', 'pages::front.show'), [
            'page' => $page,
            'seo' => $seo,
        ]);
    }

    protected function resolveSeo(array $context): mixed
    {
        $resolver = \Plugins\Seo\Contracts\SeoMetadataResolver::class;

        if (! interface_exists($resolver) || ! app()->bound($resolver)) {
            return null;
        }

        return app($resolver)->resolve($context);
    }
}
