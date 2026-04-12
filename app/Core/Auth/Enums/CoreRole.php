<?php

namespace App\Core\Auth\Enums;

enum CoreRole: string
{
    case Administrator = 'core_administrator';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Core Administrator',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Administrator => 'Papel administrativo base do core com governanca sobre o painel e a operacao central.',
        };
    }
}
