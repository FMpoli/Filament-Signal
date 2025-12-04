<div class="flex items-center justify-between gap-4">
    <div class="flex items-center gap-4">
        <span class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $name }}</span>
    </div>
    <x-filament::badge :color="$statusColor" size="sm">
        {{ $status }}
    </x-filament::badge>
</div>
