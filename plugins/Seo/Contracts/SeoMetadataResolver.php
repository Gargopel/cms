<?php

namespace Plugins\Seo\Contracts;

use Plugins\Seo\Support\SeoMetadata;

interface SeoMetadataResolver
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function resolve(array $context = []): SeoMetadata;
}
