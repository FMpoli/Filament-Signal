<?php

namespace Voodflow\Voodflow\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SignalTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'subject',
        'content_html',
        'content_text',
        'data_schema',
        'meta',
        'is_active',
    ];

    protected $casts = [
        'data_schema' => 'array',
        'meta' => 'array',
        'is_active' => 'boolean',
    ];

    public function getTable()
    {
        return config('signal.table_names.templates', parent::getTable());
    }
}
