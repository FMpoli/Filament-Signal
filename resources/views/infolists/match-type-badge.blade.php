<div class="flex items-center justify-between">
    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('filament-signal::signal.fields.match_logic') }}</span>
    <span class="inline-flex items-center rounded-md px-3 py-1 text-xs font-semibold 
        @if($matchType === 'all') bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-200
        @else bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-200
        @endif">
        {{ $text }}
    </span>
</div>

