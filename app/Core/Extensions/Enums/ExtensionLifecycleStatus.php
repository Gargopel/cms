<?php

namespace App\Core\Extensions\Enums;

enum ExtensionLifecycleStatus: string
{
    case Discovered = 'discovered';
    case Installed = 'installed';
    case Removed = 'removed';
}
