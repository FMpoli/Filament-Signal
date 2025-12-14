<?php

namespace Voodflow\Voodflow\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutionNode extends Model
{
    use HasFactory;

    protected $fillable = [
        'execution_id',
        'node_id',
        'status',
        'input',
        'output',
        'error',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('signal.table_names.execution_nodes', 'signal_execution_nodes');
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(Execution::class, 'execution_id');
    }
}
