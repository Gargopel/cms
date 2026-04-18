<?php

namespace App\Core\Media;

use App\Core\Media\Models\MediaAsset;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Plugins\Blog\Models\Post;
use Plugins\Pages\Models\Page;

class MediaUsageInspector
{
    /**
     * @return array<int, string>
     */
    public function usageSummaryFor(MediaAsset $asset): array
    {
        return $this->usageMapFor(collect([$asset]))[$asset->getKey()] ?? [];
    }

    /**
     * @param  iterable<int, MediaAsset>  $assets
     * @return array<int, array<int, string>>
     */
    public function usageMapFor(iterable $assets): array
    {
        $collection = collect($assets)
            ->filter(fn (mixed $asset): bool => $asset instanceof MediaAsset && $asset->getKey() !== null)
            ->values();

        if ($collection->isEmpty()) {
            return [];
        }

        /** @var array<int, array<int, string>> $usage */
        $usage = [];
        $assetIds = $collection->pluck('id')->all();

        foreach ($assetIds as $assetId) {
            $usage[(int) $assetId] = [];
        }

        if (class_exists(Page::class) && Schema::hasTable('plugin_pages_pages')) {
            Page::query()
                ->whereIn('featured_image_id', $assetIds)
                ->get(['id', 'title', 'featured_image_id'])
                ->each(function (Page $page) use (&$usage): void {
                    $usage[(int) $page->featured_image_id][] = 'Page: '.$page->title;
                });
        }

        if (class_exists(Post::class) && Schema::hasTable('plugin_blog_posts')) {
            Post::query()
                ->whereIn('featured_image_id', $assetIds)
                ->get(['id', 'title', 'featured_image_id'])
                ->each(function (Post $post) use (&$usage): void {
                    $usage[(int) $post->featured_image_id][] = 'Blog post: '.$post->title;
                });
        }

        return $usage;
    }
}
