<?php

namespace Base33\FilamentSignal\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignalNode extends Model
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
        return config('signal.table_names.nodes', 'signal_nodes');
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(SignalWorkflow::class, 'workflow_id');
    }
}
