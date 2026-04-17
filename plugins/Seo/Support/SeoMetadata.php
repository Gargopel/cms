<?php

namespace Plugins\Seo\Support;

class SeoMetadata
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly ?string $canonical = null,
        public readonly ?string $robots = null,
        public readonly ?string $ogTitle = null,
        public readonly ?string $ogDescription = null,
        public readonly ?string $ogType = null,
        public readonly ?string $ogUrl = null,
        public readonly ?string $ogImage = null,
    ) {
    }
}
