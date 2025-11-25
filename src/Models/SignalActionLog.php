<?php

namespace Base33\FilamentSignal\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignalActionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'trigger_id',
        'action_id',
        'event_class',
        'status',
        'attempt',
        'payload',
        'response',
        'message',
        'executed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
        'executed_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('signal.table_names.action_logs', parent::getTable());
    }

    public function trigger(): BelongsTo
    {
        return $this->belongsTo(config('signal.models.trigger', SignalTrigger::class));
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(config('signal.models.action', SignalAction::class));
    }
}
