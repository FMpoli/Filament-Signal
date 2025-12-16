<?php

namespace Voodflow\Voodflow\Models;

use Illuminate\Database\Eloquent\Model;

class InstalledPackage extends Model
{
    protected $table = 'voodflow_installed_packages';

    protected $fillable = [
        'name',
        'display_name',
        'type', // node, theme, extension
        'version',
        'description',
        'path',
        'license_key',
        'license_status', // active, invalid, expired
        'is_active',
        'metadata', // json dump of manifest
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];
}
