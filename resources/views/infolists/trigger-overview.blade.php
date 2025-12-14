@php
    /** @var \Voodflow\Voodflow\Models\SignalTrigger $record */
    $eventClassOptions = \Voodflow\Voodflow\Voodflow::eventOptions();
    $eventDisplayName = $eventClassOptions[$record->event_class] ?? class_basename($record->event_class);
    
    // Se non trovato, prova anche con getEventClassOptions
    if ($eventDisplayName === class_basename($record->event_class)) {
        try {
            $allOptions = \Voodflow\Voodflow\Filament\Resources\SignalTriggerResource::getEventClassOptions();
            $eventDisplayName = $allOptions[$record->event_class] ?? $eventDisplayName;
        } catch (\Throwable $e) {
            // Usa il nome della classe come fallback
        }
    }
    $actionsCount = $record->actions()->count();
    $matchTypeLabel = $record->match_type === 'all' 
        ? __('voodflow::signal.options.match_type.all')
        : __('voodflow::signal.options.match_type.any');
    $statusColors = [
        'active' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        'disabled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    ];
    $statusColor = $statusColors[$record->status] ?? $statusColors['draft'];
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    {{-- Left Column: Main Info --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Header Section --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $record->name }}</h2>
                    @if($record->description)
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $record->description }}</p>
                    @endif
                </div>
                <span class="inline-flex items-center rounded-md px-3 py-1 text-sm font-medium {{ $statusColor }}">
                    {{ ucfirst($record->status) }}
                </span>
            </div>
            
            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <div>
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('voodflow::signal.fields.event_class') }}
                    </div>
                    <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">
                        {{ $eventDisplayName }}
                    </div>
                    @if($record->event_class !== $eventDisplayName)
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 font-mono">
                            {{ $record->event_class }}
                        </div>
                    @endif
                </div>
                @if($record->metadata && isset($record->metadata['webhook_template']))
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ __('voodflow::signal.fields.webhook_template') }}
                        </div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">
                            {{ $record->metadata['webhook_template'] }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Event Triggered Section --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center gap-3">
                <svg class="h-6 w-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('voodflow::signal.sections.event_triggered') }}
                </h3>
            </div>
            <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                {{ __('voodflow::signal.helpers.event_triggered_description', ['event' => $eventDisplayName]) }}
            </p>
        </div>

        {{-- Conditions Checked Section --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <svg class="h-6 w-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ __('voodflow::signal.sections.conditions_checked') }}
                    </h3>
                </div>
                <span class="inline-flex items-center rounded-md bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700 dark:bg-purple-900/50 dark:text-purple-200">
                    {{ strtoupper($record->match_type === 'all' ? 'AND' : 'OR') }} ({{ $matchTypeLabel }})
                </span>
            </div>
            
            @php
                $filters = $record->filters ?? [];
                if (is_string($filters)) {
                    $filters = json_decode($filters, true) ?? [];
                }
            @endphp
            
            @if(blank($filters) || empty($filters))
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('voodflow::signal.helpers.no_filters_description') }}
                </p>
            @else
                <div class="mt-4">
                    @include('voodflow::infolists.filters-list', [
                        'filters' => $filters,
                        'matchType' => $record->match_type,
                    ])
                </div>
            @endif
        </div>

        {{-- Actions Section --}}
        <div class="space-y-4">
            @foreach($record->actions()->orderBy('execution_order')->get() as $index => $action)
                @include('voodflow::infolists.action-detail', [
                    'action' => $action,
                    'index' => $index + 1,
                ])
            @endforeach
        </div>
    </div>

    {{-- Right Column: Summary Card --}}
    <div class="lg:col-span-1">
        <div class="sticky top-6 rounded-lg border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <div class="space-y-6">
                <div>
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('voodflow::signal.fields.total_actions') }}
                    </div>
                    <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                        {{ $actionsCount }}
                    </div>
                </div>
                <div>
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('voodflow::signal.fields.match_logic') }}
                    </div>
                    <div class="mt-2 flex items-center gap-2">
                        <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            {{ __('voodflow::signal.options.match_type.' . $record->match_type) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

