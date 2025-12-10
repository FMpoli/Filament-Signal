<?php

namespace Base33\FilamentSignal\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SignalAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'trigger_id',
        'template_id',
        'name',
        'action_type',
        'is_active',
        'execution_order',
        'configuration',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'configuration' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $action): void {
            // Genera automaticamente un secret per webhook se non è presente
            if ($action->action_type === 'webhook') {
                $configuration = $action->configuration ?? [];
                $secret = Arr::get($configuration, 'secret');

                // Se il secret è vuoto o non presente, genera uno random
                if (blank($secret)) {
                    $configuration['secret'] = Str::random(40);
                    $action->configuration = $configuration;
                }
            }
        });
    }

    public function getTable()
    {
        return config('signal.table_names.actions', parent::getTable());
    }

    public function trigger(): BelongsTo
    {
        return $this->belongsTo(config('signal.models.trigger', SignalTrigger::class));
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(config('signal.models.template', SignalTemplate::class));
    }

    public function logs(): HasMany
    {
        return $this->hasMany(config('signal.models.action_log', SignalActionLog::class), 'action_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
