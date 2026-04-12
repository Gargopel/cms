<?php

namespace App\Core\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class CoreSetting extends Model
{
    protected $fillable = [
        'group_name',
        'key_name',
        'type',
        'value',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
