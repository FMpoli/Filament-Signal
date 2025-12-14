@php
    $filters = $filters ?? [];
    $matchType = $matchType ?? 'all';
    
    if (is_string($filters)) {
        $filters = json_decode($filters, true) ?? [];
    }
    
    $matchTypeLabel = $matchType === 'all' 
        ? __('voodflow::signal.options.match_type.all')
        : __('voodflow::signal.options.match_type.any');
@endphp

@if(blank($filters) || empty($filters))
    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span>{{ __('voodflow::signal.fields.no_filters_configured') }}</span>
    </div>
@else
    <div class="space-y-3">
        <div class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
            </svg>
            <span>{{ __('voodflow::signal.fields.match_type') }}: <span class="font-semibold">{{ $matchTypeLabel }}</span></span>
        </div>
        
        <div class="space-y-2">
            @foreach($filters as $index => $filter)
                @php
                    $type = $filter['type'] ?? 'unknown';
                    $data = $filter['data'] ?? [];
                    $field = $data['field'] ?? '—';
                    $value = $data['value'] ?? '—';
                    
                    $typeLabels = [
                        'equals' => __('voodflow::signal.options.filter_blocks.equals'),
                        'contains' => __('voodflow::signal.options.filter_blocks.contains'),
                    ];
                    $typeLabel = $typeLabels[$type] ?? ucfirst($type);
                @endphp
                <div class="flex items-start gap-3 rounded-lg border border-gray-200/70 bg-gray-50/50 p-3 dark:border-white/10 dark:bg-white/5">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-semibold text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                        {{ $index + 1 }}
                    </div>
                    <div class="flex-1 space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                {{ $typeLabel }}
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                {{ __('voodflow::signal.fields.field') }}: <span class="ml-1 font-semibold">{{ $field }}</span>
                            </span>
                            <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                            <span class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-200">
                                {{ __('voodflow::signal.fields.value') }}: <span class="ml-1 font-semibold">{{ $value }}</span>
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

