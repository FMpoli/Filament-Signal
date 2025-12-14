<?php

namespace Voodflow\Voodflow\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Edge extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'edge_id',
        'source_node_id',
        'target_node_id',
        'source_handle',
        'target_handle',
    ];

    public function getTable()
    {
        return config('signal.table_names.edges', 'signal_edges');
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }
}
