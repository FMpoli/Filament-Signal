@php
    $matchType = $matchType ?? 'all';
    $filters = $filters ?? [];
    $totalActions = $totalActions ?? 0;

    if (is_string($filters)) {
        $filters = json_decode($filters, true) ?? [];
    }

    $hasFilters = !blank($filters) && !empty($filters);
    $matchTypeLabel = $matchType === 'all'
        ? __('voodflow::signal.options.match_type.all')
        : __('voodflow::signal.options.match_type.any');
    $matchTypeDescription = $matchType === 'all'
        ? __('voodflow::signal.fields.match_all_description')
        : __('voodflow::signal.fields.match_any_description');
@endphp

<div class=">

    {{-- Body --}}
    <div class="px-4 py-6">
        <div class="mb-1">
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ $matchTypeLabel }}
            </div>
            <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ $matchTypeDescription }}
            </div>
        </div>

        @if(!$hasFilters)
            <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('voodflow::signal.fields.no_filters_configured') }}
                <span class="text-gray-400 dark:text-gray-500">({{ __('voodflow::signal.fields.runs_always') }})</span>
            </div>
        @endif
    </div>

    {{-- Footer --}}

            <span class="text-xs font-semibold tracking-wide text-gray-600 uppercase dark:text-gray-400">
                {{ __('voodflow::signal.fields.total_actions') }}
            </span>
            <span class="text-xl font-bold text-gray-900 dark:text-gray-100">
                {{ $totalActions }}
            </span>
</div>

