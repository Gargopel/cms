<?php

namespace Plugins\Seo\Support;

use App\Core\Extensions\Settings\PluginSettingsManager;
use Illuminate\Support\Str;
use Plugins\Seo\Contracts\SeoMetadataResolver;

class DefaultSeoMetadataResolver implements SeoMetadataResolver
{
    public function __construct(
        protected PluginSettingsManager $settings,
    ) {
    }

    public function resolve(array $context = []): SeoMetadata
    {
        $titleBase = $this->stringOrNull($context['title'] ?? null) ?? config('app.name', 'CMS Platform');
        $titleSuffix = $this->suffixOrEmpty($this->settings->get('seo', 'default_meta_title_suffix', ''));
        $title = trim($titleBase.$titleSuffix);

        $description = $this->stringOrNull($context['description'] ?? null)
            ?? $this->stringOrNull($this->settings->get('seo', 'default_meta_description'))
            ?? 'CMS plugin-first construido em Laravel.';

        $canonical = $this->stringOrNull($context['canonical'] ?? null);
        $indexingEnabled = (bool) $this->settings->get('seo', 'indexing_enabled', true);
        $forceNoindex = (bool) ($context['noindex'] ?? false);
        $robots = $forceNoindex || ! $indexingEnabled ? 'noindex, nofollow' : 'index, follow';
        $ogType = $this->stringOrNull($context['og_type'] ?? null) ?? 'website';
        $ogImage = $this->stringOrNull($context['og_image'] ?? null);

        return new SeoMetadata(
            title: $title,
            description: Str::limit(trim(strip_tags($description)), 160, ''),
            canonical: $canonical,
            robots: $robots,
            ogTitle: $title,
            ogDescription: Str::limit(trim(strip_tags($description)), 160, ''),
            ogType: $ogType,
            ogUrl: $canonical,
            ogImage: $ogImage,
        );
    }

    protected function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    protected function suffixOrEmpty(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return '';
        }

        return ' '.$trimmed;
    }
}
