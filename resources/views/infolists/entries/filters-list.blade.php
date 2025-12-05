@php
    $filters = $getState();
    $matchType = $record->match_type ?? 'all';

    if (is_string($filters)) {
        $filters = json_decode($filters, true) ?? [];
    }

    // Prova a recuperare i filtri dal record originale se sono vuoti
    if (empty($filters) && isset($record)) {
        try {
            $rawFilters = $record->getRawOriginal('filters');
            if ($rawFilters !== null && $rawFilters !== '') {
                $decoded = json_decode($rawFilters, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $filters = $decoded;
                }
            }
        } catch (\Exception $e) {
            // Ignora errori
        }
    }

    $matchTypeDisplay = $matchType === 'all'
        ? __('filament-signal::signal.fields.all_conditions')
        : __('filament-signal::signal.fields.any_condition');
@endphp

<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    @if(blank($filters) || empty($filters))
        <div class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ __('filament-signal::signal.fields.no_filters_configured') }}
        </div>
    @else
        <div class="space-y-3">
            @foreach($filters as $index => $filter)
                @php
                    $type = $filter['type'] ?? 'equals';
                    $data = $filter['data'] ?? [];
                    $field = $data['field'] ?? '—';
                    $value = $data['value'] ?? '—';

                    $typeLabels = [
                        'equals' => __('filament-signal::signal.options.filter_blocks.equals'),
                        'contains' => __('filament-signal::signal.options.filter_blocks.contains'),
                    ];
                    $typeLabel = strtolower($typeLabels[$type] ?? ucfirst($type));
                @endphp

                {{-- Filter Block --}}
                <div class="flex items-center gap-3">
                    {{-- Icon Button --}}
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-primary-600 text-white dark:bg-primary-700">
                        @if($type === 'contains')
                            <x-filament::icon icon="heroicon-o-magnifying-glass" class="h-5 w-5" />
                        @else
                            <span class="text-lg font-bold">=</span>
                        @endif
                    </div>

                    {{-- Field and Value Box --}}
                    <div class="flex-1 rounded-lg bg-gray-100 px-4 py-3 dark:bg-gray-900">
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            <span class="text-gray-600 dark:text-gray-400">{{ $field }}</span>
                            <span class="mx-2 text-gray-500 dark:text-gray-500">{{ $typeLabel }}</span>
                            <span class="font-semibold text-primary-600 dark:text-primary-400">{{ $value }}</span>
                        </div>
                    </div>
                </div>

                {{-- AND/OR Connector --}}
                @if(!$loop->last)
                    <div class="flex justify-center">
                        <div class="rounded bg-primary-600 px-3 py-1 text-xs font-bold uppercase text-white dark:bg-primary-700">
                            {{ $matchType === 'all' ? 'AND' : 'OR' }}
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        {{-- Footer --}}
        <div class="mt-4 flex items-center justify-between rounded-lg bg-gray-100 px-4 py-3 dark:bg-gray-900">
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                {{ __('filament-signal::signal.fields.match_logic') }}
            </span>
            <div class="rounded bg-gray-200 px-3 py-1 text-xs font-bold uppercase text-gray-800 dark:bg-gray-700 dark:text-white">
                {{ $matchTypeDisplay }}
            </div>
        </div>
    @endif
</x-dynamic-component>
