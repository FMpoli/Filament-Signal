@php
    // Supporta sia il contesto ViewField che Placeholder
    $actions = collect($actions ?? (isset($component) && method_exists($component, 'getState') ? $component->getState() : null) ?? []);
@endphp

<div class="space-y-4">
    @forelse ($actions as $action)
        @php
            $configuration = $action['configuration'] ?? [];
        @endphp
        <div class="rounded-lg border border-gray-200/70 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="flex flex-wrap items-center gap-4 text-sm">
                <div>
                    <div class="font-semibold text-gray-900 dark:text-gray-100">
                        {{ $action['name'] ?? '—' }}
                    </div>
                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ $action['action_type'] ?? '-' }}
                    </div>
                </div>
                <div class="ms-auto flex flex-wrap items-center gap-4 text-gray-600 dark:text-gray-300">
                    <span>
                        {{ __('filament-signal::signal.fields.execution_order') }}:
                        <span class="font-semibold">{{ $action['execution_order'] ?? '-' }}</span>
                    </span>
                    @if ($action['action_type'] === 'webhook')
                        <span>Method: <span class="font-semibold">{{ strtoupper($configuration['method'] ?? 'POST') }}</span></span>
                    @endif
                </div>
            </div>

            @if ($action['action_type'] === 'webhook')
                <div class="mt-4 grid gap-3 text-xs text-gray-500 dark:text-gray-400 md:grid-cols-2">
                    <div>URL: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $configuration['url'] ?? '—' }}</span></div>
                    <div>Queue: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $configuration['queue'] ?? 'default' }}</span></div>
                    <div>Connection: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $configuration['connection'] ?? '—' }}</span></div>
                    <div>Tags: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ implode(', ', $configuration['tags'] ?? []) ?: '—' }}</span></div>
                </div>
            @endif

            <div class="mt-4 text-sm text-gray-600 dark:text-gray-300">
                <div class="mb-2 font-semibold">
                    {{ __('filament-signal::signal.fields.configuration') }}
                </div>
                @if (blank($configuration))
                    <div class="italic text-gray-400 dark:text-gray-500">—</div>
                @else
                    <pre class="max-h-64 overflow-auto rounded-md bg-gray-950/5 p-3 text-xs font-mono text-gray-900 dark:bg-white/10 dark:text-gray-100">
{{ json_encode($configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}
                    </pre>
                @endif
            </div>
        </div>
    @empty
        <div class="text-sm italic text-gray-400 dark:text-gray-500">—</div>
    @endforelse
</div>


