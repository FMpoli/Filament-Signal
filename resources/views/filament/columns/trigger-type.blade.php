@php
    $triggerNode = $record->getTriggerNode();
    $icon = 'heroicon-o-minus-circle';
    $label = '—';
    $tooltip = 'No trigger';
    $colorClass = 'text-gray-400';
    
    if ($triggerNode) {
        $data = $triggerNode->data ?? [];
        $event = $data['selectedEvent'] ?? null;
        
        if ($event) {
            $parts = explode('\\', $event);
            $className = end($parts);
            
            $isScheduled = str_contains(strtolower($event), 'schedule') || 
                          str_contains(strtolower($event), 'cron');
            $isWebhook = str_contains(strtolower($event), 'webhook') ||
                        str_contains(strtolower($event), 'http');
            $isEloquent = str_contains(strtolower($event), 'eloquent') ||
                         str_contains(strtolower($className), 'created') ||
                         str_contains(strtolower($className), 'updated') ||
                         str_contains(strtolower($className), 'deleted');
            
            if ($isScheduled) {
                $icon = 'heroicon-o-clock';
                $label = 'Schedule';
                $tooltip = 'Scheduled trigger';
                $colorClass = 'text-blue-600 dark:text-blue-400';
            } elseif ($isWebhook) {
                $icon = 'heroicon-o-globe-alt';
                $label = 'Webhook';
                $tooltip = 'HTTP webhook';
                $colorClass = 'text-green-600 dark:text-green-400';
            } elseif ($isEloquent) {
                $icon = 'heroicon-o-database';
                $label = 'Database';
                $tooltip = "Eloquent: {$className}";
                $colorClass = 'text-purple-600 dark:text-purple-400';
            } else {
                $icon = 'heroicon-o-bolt';
                $label = 'Event';
                $tooltip = $className;
                $colorClass = 'text-amber-600 dark:text-amber-400';
            }
        }
    }
@endphp

<div class="flex items-center justify-center gap-1.5" title="{{ $tooltip }}">
    <x-filament::icon :icon="$icon" class="w-4 h-4 {{ $colorClass }}" />
    <span class="text-xs font-medium {{ $colorClass }}">{{ $label }}</span>
</div>
