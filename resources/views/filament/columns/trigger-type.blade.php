@php
    $triggerNode = $record->getTriggerNode();
    $triggerType = null;
    $triggerLabel = 'Not set';
    $icon = 'heroicon-o-question-mark-circle';
    $tooltipText = 'No trigger configured';
    
    if ($triggerNode) {
        $data = $triggerNode->data ?? [];
        $selectedEvent = $data['selectedEvent'] ?? null;
        
        if ($selectedEvent) {
            // Parse the event class to get a user-friendly label
            $parts = explode('\\', $selectedEvent);
            $className = end($parts);
            
            // Determine if it's a scheduled/time-based trigger
            $isScheduled = str_contains(strtolower($selectedEvent), 'schedule') || 
                          str_contains(strtolower($selectedEvent), 'cron') ||
                          str_contains(strtolower($selectedEvent), 'timer');
            
            $isWebhook = str_contains(strtolower($selectedEvent), 'webhook') ||
                        str_contains(strtolower($selectedEvent), 'http');
            
            $isEloquent = str_contains(strtolower($selectedEvent), 'eloquent') ||
                         str_contains(strtolower($className), 'created') ||
                         str_contains(strtolower($className), 'updated') ||
                         str_contains(strtolower($className), 'deleted');
            
            if ($isScheduled) {
                $icon = 'heroicon-o-clock';
                $triggerLabel = 'Scheduled';
                $tooltipText = 'Time-based trigger: Every data arrives';
            } elseif ($isWebhook) {
                $icon = 'heroicon-o-globe-alt';
                $triggerLabel = 'Webhook';
                $tooltipText = 'HTTP webhook trigger';
            } elseif ($isEloquent) {
                $icon = 'heroicon-o-database';
                $triggerLabel = 'Database';
                $tooltipText = "Eloquent event: {$className}";
            } else {
                $icon = 'heroicon-o-bolt';
                $triggerLabel = 'Event';
                $tooltipText = "Event: {$className}";
            }
        }
    }
@endphp

<div class="flex items-center justify-center gap-2">
    <div 
        class="flex items-center gap-1 px-2 py-1 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors cursor-help"
        title="{{ $tooltipText }}"
        x-data 
        x-tooltip="'{{ $tooltipText }}'"
    >
        <x-filament::icon 
            :icon="$icon" 
            class="w-4 h-4"
        />
        <span class="text-xs font-medium">{{ $triggerLabel }}</span>
    </div>
</div>
