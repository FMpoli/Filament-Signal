@php
    /** @var \Filament\Forms\Components\ViewField $component */
    $actions = collect($component->getState() ?? []);
@endphp

<div class="space-y-4">
    @forelse ($actions as $action)
        <div class="rounded-lg border border-gray-200/70 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="flex flex-wrap items-center gap-4">
                <div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        {{ $action['name'] ?? '—' }}
                    </div>
                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ $action['action_type'] ?? '-' }}
                    </div>
                </div>
                <div class="ms-auto text-sm text-gray-600 dark:text-gray-300">
                    {{ __('filament-signal::signal.fields.execution_order') }}:
                    <span class="font-semibold">{{ $action['execution_order'] ?? '-' }}</span>
                </div>
            </div>

            <div class="mt-4 text-sm text-gray-600 dark:text-gray-300">
                <div class="mb-2 font-semibold">
                    {{ __('filament-signal::signal.fields.configuration') }}
                </div>
                @php
                    $configuration = $action['configuration'] ?? [];
                @endphp
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

