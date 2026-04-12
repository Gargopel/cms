<?php

namespace Plugins\Pages\Enums;

enum PageStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::cases(),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
        };
    }
}
