<?php

namespace Voodflow\Voodflow\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Execution extends Model
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
        return config('voodflow.table_names.executions', 'signal_executions');
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }

    public function executionNodes(): HasMany
    {
        return $this->hasMany(ExecutionNode::class, 'execution_id');
    }
}
