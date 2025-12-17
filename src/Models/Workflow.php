<?php

namespace Voodflow\Voodflow\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'metadata',
        'user_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class));
    }

    /**
     * Get the trigger node for this workflow
     */
    public function getTriggerNode(): ?Node
    {
        return $this->nodes()
            ->where('type', 'trigger')
            ->first();
    }

    /**
     * Get trigger type information
     */
    public function getTriggerTypeAttribute(): ?string
    {
        $triggerNode = $this->getTriggerNode();
        
        if (!$triggerNode) {
            return null;
        }

        $config = $triggerNode->config ?? [];
        return $config['selectedEvent'] ?? null;
    }

    /**
     * Get a preview of nodes for the table display
     */
    public function getNodesPreviewAttribute(): array
    {
        return $this->nodes()
            ->orderBy('id')
            ->limit(5)
            ->get()
            ->map(function ($node) {
                return [
                    'type' => $node->type,
                    'config' => $node->config,
                    'icon' => $this->getNodeIcon($node->type, $node->config),
                ];
            })
            ->toArray();
    }

    /**
     * Get icon for a node based on its type and data
     */
    private function getNodeIcon(string $type, ?array $data = null): string
    {
        // Check if it's a custom node with manifest
        if ($type !== 'trigger' && $type !== 'action' && $type !== 'filter' && $type !== 'conditional') {
            $manifestPath = storage_path("app/voodflow/nodes/{$type}/manifest.json");
            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                return $manifest['icon'] ?? 'heroicon-o-puzzle-piece';
            }
        }

        // Default icons for built-in types
        return match($type) {
            'trigger' => 'heroicon-o-bolt',
            'action' => 'heroicon-o-play',
            'filter' => 'heroicon-o-funnel',
            'conditional' => 'heroicon-o-code-bracket',
            default => 'heroicon-o-puzzle-piece',
        };
    }
}
