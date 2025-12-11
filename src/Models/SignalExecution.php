<?php

namespace Base33\FilamentSignal\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SignalExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'status',
        'started_at',
        'finished_at',
        'input_context',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'input_context' => 'array',
    ];

    public function getTable()
    {
        return config('signal.table_names.executions', 'signal_executions');
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(SignalWorkflow::class, 'workflow_id');
    }

    public function executionNodes(): HasMany
    {
        return $this->hasMany(SignalExecutionNode::class, 'execution_id');
    }
}
