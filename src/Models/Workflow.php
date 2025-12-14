<?php

namespace Voodflow\Voodflow\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
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
        return config('voodflow.table_names.workflows', 'signal_workflows');
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(Node::class, 'workflow_id');
    }

    public function edges(): HasMany
    {
        return $this->hasMany(Edge::class, 'workflow_id');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(Execution::class, 'workflow_id');
    }
}
