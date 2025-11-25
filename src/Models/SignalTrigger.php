<?php

namespace Base33\FilamentSignal\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SignalTrigger extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DISABLED = 'disabled';

    public const MATCH_ALL = 'all';

    public const MATCH_ANY = 'any';

    protected $fillable = [
        'name',
        'event_class',
        'description',
        'status',
        'match_type',
        'filters',
        'metadata',
        'activated_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'metadata' => 'array',
        'activated_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('signal.table_names.triggers', parent::getTable());
    }

    public function actions(): HasMany
    {
        return $this->hasMany(config('signal.models.action', SignalAction::class), 'trigger_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(config('signal.models.action_log', SignalActionLog::class));
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
