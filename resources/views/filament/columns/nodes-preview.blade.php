@php
    $maxDisplay = 4; // Display max 4 nodes, then +X indicator
@endphp

<div class="flex items-center gap-1">
    @forelse(array_slice($nodesPreview, 0, $maxDisplay) as $index => $nodeInfo)
        @php
            $icon = $nodeInfo['icon'] ?? 'heroicon-o-puzzle-piece';
            $type = $nodeInfo['type'] ?? 'unknown';
            $bgColor = match($type) {
                'trigger' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
                'action' => 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
                'filter' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
                'conditional' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
                default => 'bg-gray-100 dark:bg-gray-900/30 text-gray-600 dark:text-gray-400',
            };
        @endphp
        
        <div 
            class="flex items-center justify-center w-8 h-8 rounded-full {{ $bgColor }} transition-all duration-200 hover:scale-110"
            title="{{ ucfirst($type) }}"
        >
            <x-filament::icon 
                :icon="$icon" 
                class="w-4 h-4"
            />
        </div>
    @empty
        <span class="text-sm text-gray-400 dark:text-gray-600">No nodes</span>
    @endforelse
    
    @if(count($nodesPreview) > $maxDisplay)
        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs font-medium">
            +{{ count($nodesPreview) - $maxDisplay }}
        </div>
    @endif
</div>
