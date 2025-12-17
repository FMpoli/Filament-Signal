@php
    $matchType = $matchType ?? 'all';
    $filters = $filters ?? [];
    $operatorLabel = $matchType === 'all' ? 'AND' : 'OR';
@endphp

<div class="space-y-3">
    @if(empty($filters))
        <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('voodflow::signal.fields.no_filters_configured') }} ({{ __('voodflow::signal.fields.runs_always') }})
        </div>
    @else
        @foreach($filters as $index => $filter)
            @php
                $type = $filter['type'] ?? null;
                $data = $filter['data'] ?? [];

                if (!is_array($data)) {
                    $data = (array) $data;
                }

                $field = $data['field'] ?? '—';
                $value = $data['value'] ?? '—';

                $typeLabels = [
                    'equals' => 'EQUALS',
                    'contains' => 'CONTAINS',
                ];
                $operator = $typeLabels[$type] ?? strtoupper($type ?? 'EQUALS');

                $icon = $type === 'contains' ? 'heroicon-o-magnifying-glass' : 'heroicon-o-equals';
            @endphp

            @if($index > 0)
                <div class="flex justify-center">
                    <span class="inline-flex items-center px-3 py-1 text-xs font-medium text-gray-800 bg-gray-200 rounded-lg dark:bg-gray-700 dark:text-gray-200">
                        {{ $operatorLabel }}
                    </span>
                </div>
            @endif

            <div class="flex items-center gap-3 p-3 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="shrink-0">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-100 rounded dark:bg-gray-700">
                        <x-filament::icon :icon="$icon" class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                    </div>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $field }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $operator }}</span>
                        <span class="text-sm font-medium text-green-600 dark:text-green-400">{{ $value }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>

