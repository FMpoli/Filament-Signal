@php
    $maxDisplay = 4;
    $nodes = array_slice($nodesPreview, 0, $maxDisplay);
    $remaining = count($nodesPreview) - $maxDisplay;
@endphp

<div class="flex items-center gap-1.5">
    @foreach($nodes as $nodeInfo)
        @php
            $type = $nodeInfo['type'] ?? 'unknown';
            $icon = $nodeInfo['icon'] ?? 'heroicon-o-puzzle-piece';
            $bgColor = match($type) {
                'trigger' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
                'action' => 'bg-green-500/10 text-green-600 dark:text-green-400',
                'filter' => 'bg-purple-500/10 text-purple-600 dark:text-purple-400',
                'conditional' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
                default => 'bg-gray-500/10 text-gray-600 dark:text-gray-400',
            };
        @endphp
        <div class="flex items-center justify-center w-7 h-7 rounded-full {{ $bgColor }}" title="{{ ucfirst($type) }}">
            <x-filament::icon :icon="$icon" class="w-3.5 h-3.5" />
        </div>
    @endforeach
    
    @if($remaining > 0)
        <div class="flex items-center justify-center w-7 h-7 rounded-full bg-gray-500/10 text-gray-600 dark:text-gray-400 text-[10px] font-semibold">
            +{{ $remaining }}
        </div>
    @endif
    
    @if(empty($nodesPreview))
        <span class="text-xs text-gray-400">—</span>
    @endif
</div>
