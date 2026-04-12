<?php

namespace App\Core\Health\Enums;

enum HealthStatus: string
{
    case Ok = 'ok';
    case Warning = 'warning';
    case Error = 'error';
}
