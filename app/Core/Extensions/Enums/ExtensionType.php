<?php

namespace App\Core\Extensions\Enums;

enum ExtensionType: string
{
    case Plugin = 'plugin';
    case Theme = 'theme';
}
