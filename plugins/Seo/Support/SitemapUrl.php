<?php

namespace Plugins\Seo\Support;

use Carbon\CarbonInterface;

class SitemapUrl
{
    public function __construct(
        public readonly string $location,
        public readonly ?CarbonInterface $lastModified = null,
    ) {
    }
}
