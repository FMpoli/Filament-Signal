<?php

namespace Base33\FilamentSignal\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignalExecutionNode extends Model
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
        return $this->belongsTo(SignalExecution::class, 'execution_id');
    }
}
