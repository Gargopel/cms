<?php

namespace App\Core\Extensions\Enums;

enum ExtensionDiscoveryStatus: string
{
    case Valid = 'valid';
    case Invalid = 'invalid';
    case Incompatible = 'incompatible';
}
