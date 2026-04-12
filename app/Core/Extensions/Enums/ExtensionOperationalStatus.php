<?php

namespace App\Core\Extensions\Enums;

enum ExtensionOperationalStatus: string
{
    case Discovered = 'discovered';
    case Installed = 'installed';
    case Enabled = 'enabled';
    case Disabled = 'disabled';
}
