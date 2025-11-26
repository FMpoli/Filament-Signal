@php
    /** @var \Filament\Forms\Components\ViewField $component */
    $state = $component->getState();

    $formatted = blank($state)
        ? null
        : (is_string($state)
            ? $state
            : (json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: null));
@endphp

<div class="space-y-2">
    @if (filled($component->getLabel()))
        <div class="text-sm font-semibold text-gray-600 dark:text-gray-300">
            {{ $component->getLabel() }}
        </div>
    @endif

    @if (blank($formatted))
        <div class="text-sm italic text-gray-400 dark:text-gray-500">—</div>
    @else
        <pre class="max-h-64 overflow-auto rounded-lg bg-gray-950/5 p-3 text-xs font-mono text-gray-900 dark:bg-white/5 dark:text-gray-100">{{ $formatted }}</pre>
    @endif
</div>

