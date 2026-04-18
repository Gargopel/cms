<?php

namespace Plugins\Seo\Support;

use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use Illuminate\Support\Facades\Schema;
use Plugins\Blog\Models\Category;
use Plugins\Blog\Models\Post;
use Plugins\Blog\Models\Tag;
use Plugins\Pages\Models\Page;

class SeoSitemapGenerator
{
    /**
     * @return array<int, SitemapUrl>
     */
    public function generate(): array
    {
        $entries = [
            new SitemapUrl(url('/')),
        ];

        if ($this->pluginIsPubliclyAvailable('pages') && class_exists(Page::class)) {
            $entries = [
                ...$entries,
                ...Page::query()
                    ->published()
                    ->orderBy('slug')
                    ->get(['slug', 'updated_at'])
                    ->map(
                        fn (Page $page): SitemapUrl => new SitemapUrl(
                            location: url('/pages/'.$page->slug),
                            lastModified: $page->updated_at,
                        )
                    )
                    ->all(),
            ];
        }

        if ($this->pluginIsPubliclyAvailable('blog') && class_exists(Post::class)) {
            $blogIndexLastModified = Post::query()
                ->published()
                ->orderByDesc('updated_at')
                ->first(['updated_at'])
                ?->updated_at;

            $entries = [
                ...$entries,
                new SitemapUrl(
                    location: url('/blog'),
                    lastModified: $blogIndexLastModified,
                ),
                ...Post::query()
                    ->published()
                    ->orderByDesc('published_at')
                    ->orderByDesc('updated_at')
                    ->get(['slug', 'updated_at'])
                    ->map(
                        fn (Post $post): SitemapUrl => new SitemapUrl(
                            location: url('/blog/'.$post->slug),
                            lastModified: $post->updated_at,
                        )
                    )
                    ->all(),
                ...Category::query()
                    ->whereHas('posts', fn ($query) => $query->published())
                    ->orderBy('slug')
                    ->get(['slug', 'updated_at'])
                    ->map(
                        fn (Category $category): SitemapUrl => new SitemapUrl(
                            location: url('/blog/category/'.$category->slug),
                            lastModified: $category->updated_at,
                        )
                    )
                    ->all(),
                ...Tag::query()
                    ->whereHas('posts', fn ($query) => $query->published())
                    ->orderBy('slug')
                    ->get(['slug', 'updated_at'])
                    ->map(
                        fn (Tag $tag): SitemapUrl => new SitemapUrl(
                            location: url('/blog/tag/'.$tag->slug),
                            lastModified: $tag->updated_at,
                        )
                    )
                    ->all(),
            ];
        }

        return $entries;
    }

    protected function pluginIsPubliclyAvailable(string $pluginSlug): bool
    {
        if (! Schema::hasTable('extension_records')) {
            return false;
        }

        $record = ExtensionRecord::query()
            ->where('type', ExtensionType::Plugin->value)
            ->where('slug', $pluginSlug)
            ->first();

        if (! $record instanceof ExtensionRecord) {
            return false;
        }

        return $record->discovery_status === ExtensionDiscoveryStatus::Valid
            && $record->administrativeLifecycleStatus() === ExtensionLifecycleStatus::Installed
            && $record->operational_status === ExtensionOperationalStatus::Enabled;
    }
}
