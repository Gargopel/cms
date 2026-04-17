<?php

namespace Plugins\Blog\Http\Controllers;

use App\Core\Extensions\Settings\PluginSettingsManager;
use App\Core\Themes\ThemeViewResolver;
use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Plugins\Blog\Models\Category;
use Plugins\Blog\Models\Post;
use Plugins\Blog\Models\Tag;

class PublicBlogController extends Controller
{
    public function index(ThemeViewResolver $themes, PluginSettingsManager $pluginSettings): View
    {
        $blogTitle = (string) $pluginSettings->get('blog', 'blog_title', 'Blog');
        $blogIntro = (string) $pluginSettings->get('blog', 'blog_intro', 'Listagem publica editorial do plugin oficial Blog.');
        $showExcerpts = (bool) $pluginSettings->get('blog', 'show_excerpts', true);

        return view($themes->resolve('plugins.blog.index', 'blog::front.index'), [
            'posts' => Post::query()
                ->with(['featuredImage', 'category', 'tags'])
                ->published()
                ->orderByDesc('published_at')
                ->orderByDesc('updated_at')
                ->paginate(12),
            'blogTitle' => $blogTitle,
            'blogIntro' => $blogIntro,
            'showExcerpts' => $showExcerpts,
            'seo' => $this->resolveSeo([
                'title' => $blogTitle,
                'description' => $blogIntro,
                'canonical' => url('/blog'),
                'og_type' => 'website',
            ]),
        ]);
    }

    public function show(string $slug, ThemeViewResolver $themes): View
    {
        $post = Post::query()
            ->with(['featuredImage', 'category', 'tags'])
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view($themes->resolve('plugins.blog.show', 'blog::front.show'), [
            'post' => $post,
            'seo' => $this->resolveSeo([
                'title' => $post->title,
                'description' => $post->excerpt,
                'canonical' => url('/blog/'.$post->slug),
                'og_type' => 'article',
                'og_image' => $post->featuredImage?->url(),
            ]),
        ]);
    }

    public function category(string $slug, ThemeViewResolver $themes, PluginSettingsManager $pluginSettings): View
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->firstOrFail();

        return view($themes->resolve('plugins.blog.category', 'blog::front.category'), [
            'category' => $category,
            'posts' => Post::query()
                ->with(['featuredImage', 'category', 'tags'])
                ->published()
                ->where('category_id', $category->getKey())
                ->orderByDesc('published_at')
                ->orderByDesc('updated_at')
                ->paginate(12),
            'blogTitle' => (string) $pluginSettings->get('blog', 'blog_title', 'Blog'),
            'showExcerpts' => (bool) $pluginSettings->get('blog', 'show_excerpts', true),
            'seo' => $this->resolveSeo([
                'title' => $category->name,
                'description' => $category->description ?: 'Posts publicados filtrados por categoria editorial.',
                'canonical' => url('/blog/category/'.$category->slug),
                'og_type' => 'website',
            ]),
        ]);
    }

    public function tag(string $slug, ThemeViewResolver $themes, PluginSettingsManager $pluginSettings): View
    {
        $tag = Tag::query()
            ->where('slug', $slug)
            ->firstOrFail();

        return view($themes->resolve('plugins.blog.tag', 'blog::front.tag'), [
            'tag' => $tag,
            'posts' => Post::query()
                ->with(['featuredImage', 'category', 'tags'])
                ->published()
                ->whereHas('tags', fn ($query) => $query->where('plugin_blog_tags.id', $tag->getKey()))
                ->orderByDesc('published_at')
                ->orderByDesc('updated_at')
                ->paginate(12),
            'blogTitle' => (string) $pluginSettings->get('blog', 'blog_title', 'Blog'),
            'showExcerpts' => (bool) $pluginSettings->get('blog', 'show_excerpts', true),
            'seo' => $this->resolveSeo([
                'title' => $tag->name,
                'description' => $tag->description ?: 'Posts publicados filtrados por tag editorial.',
                'canonical' => url('/blog/tag/'.$tag->slug),
                'og_type' => 'website',
            ]),
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
