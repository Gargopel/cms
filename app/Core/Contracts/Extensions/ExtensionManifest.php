<?php

namespace App\Core\Contracts\Extensions;

use App\Core\Extensions\Enums\ExtensionType;

interface ExtensionManifest
{
    public function type(): ExtensionType;

    public function name(): string;

    public function slug(): string;

    public function description(): string;

    public function version(): string;

    public function author(): string;

    public function vendor(): ?string;

    public function minCoreVersion(): string;

    public function maxCoreVersion(): ?string;

    public function path(): string;

    public function manifestPath(): string;

    public function provider(): ?string;

    public function critical(): bool;

    public function requires(): array;

    public function capabilities(): array;

    public function permissions(): array;

    public function settings(): array;

    public function extra(): array;

    public function toArray(): array;
}
