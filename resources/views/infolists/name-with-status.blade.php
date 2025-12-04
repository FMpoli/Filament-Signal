<div class="flex items-center gap-2">
    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $name }}</h2>
    <span class="inline-flex items-center rounded-md px-2.5 py-0.5 text-xs font-medium 
        @if($statusColor === 'success') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
        @elseif($statusColor === 'danger') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
        @else bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300
        @endif">
        {{ $status }}
    </span>
</div>

