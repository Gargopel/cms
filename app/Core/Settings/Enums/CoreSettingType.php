<?php

namespace App\Core\Settings\Enums;

enum CoreSettingType: string
{
    case String = 'string';
    case Boolean = 'boolean';
    case Text = 'text';
}
