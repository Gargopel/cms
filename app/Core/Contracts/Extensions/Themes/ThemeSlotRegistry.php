<?php

namespace App\Core\Contracts\Extensions\Themes;

use App\Core\Extensions\Hooks\ThemeSlotBlock;

interface ThemeSlotRegistry
{
    public function registerThemeSlotBlock(ThemeSlotBlock $block): void;

    /**
     * @return array<int, ThemeSlotBlock>
     */
    public function themeSlotBlocks(string $slot): array;
}
