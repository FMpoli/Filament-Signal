@php
    $value = $state ?? [];
    $pretty = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp

<div class="space-y-2">
    <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $title ?? '' }}</h4>
    <pre class="text-xs whitespace-pre-wrap bg-gray-100 dark:bg-gray-800 rounded p-3 overflow-auto max-h-64">{{ $pretty }}</pre>
</div>

