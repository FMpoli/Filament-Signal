<?php

namespace Base33\FilamentSignal\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SignalWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function getTable()
    {
        return config('signal.table_names.workflows', 'signal_workflows');
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(SignalNode::class, 'workflow_id');
    }

    public function edges(): HasMany
    {
        return $this->hasMany(SignalEdge::class, 'workflow_id');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(SignalExecution::class, 'workflow_id');
    }
}
