<div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
    <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
        {{ $title }}
    </div>
    <div class="mt-2 flex items-center gap-2">
        @if(isset($icon))
            <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                @if($icon === 'heroicon-o-rocket-launch')
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                @elseif($icon === 'heroicon-o-funnel')
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                @endif
            </svg>
        @endif
        @if(is_numeric($value))
            <span class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $value }}</span>
        @else
            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $value }}</span>
        @endif
    </div>
</div>

