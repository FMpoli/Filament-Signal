<?php

namespace Voodflow\Voodflow\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Node extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'node_id',
        'type',
        'name',
        'config',
        'position',
    ];

    protected $casts = [
        'config' => 'array',
        'position' => 'array',
    ];

    public function getTable()
    {
        return config('voodflow.table_names.nodes', 'signal_nodes');
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }
}
