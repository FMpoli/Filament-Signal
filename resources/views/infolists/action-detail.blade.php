@php
    /** @var \Voodflow\Voodflow\Models\SignalAction $action */
    $configuration = $action->configuration ?? [];
    $actionType = $action->action_type ?? 'unknown';
    $name = $action->name ?? '—';
    $executionOrder = $action->execution_order ?? $index ?? '-';
    $isActive = $action->is_active ?? true;

    $typeColors = [
        'log' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-200',
        'webhook' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-200',
    ];
    $typeColor = $typeColors[$actionType] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200';
@endphp

<div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
    {{-- Action Header --}}
    <div class="flex items-center gap-3 mb-6">
        <svg class="h-6 w-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
        </svg>
        <div class="flex-1">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ __('filament-signal::signal.actions.action_number', ['number' => $executionOrder, 'name' => $name]) }}
            </h3>
            <div class="mt-1 flex items-center gap-2">
                <span
                    class="inline-flex items-center rounded-md {{ $typeColor }} px-2.5 py-0.5 text-xs font-medium uppercase tracking-wide">
                    {{ $actionType }}
                </span>
                @if(!$isActive)
                    <span
                        class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        {{ __('filament-signal::signal.options.status.disabled') }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Webhook Destination --}}
    @if ($actionType === 'webhook')
        <div class="mb-6 space-y-4">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                {{ __('filament-signal::signal.sections.destination') }}
            </h4>
            <div class="grid gap-4 md:grid-cols-3">
                <div class="md:col-span-2">
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-300 mb-1">
                        {{ __('filament-signal::signal.fields.endpoint_url') }}
                    </div>
                    <div
                        class="break-all font-mono text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-800/50 px-3 py-2 rounded-md">
                        {{ $configuration['url'] ?? '—' }}
                    </div>
                </div>
                <div>
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-300 mb-1">
                        {{ __('filament-signal::signal.fields.http_method') }}
                    </div>
                    <span
                        class="inline-flex items-center rounded-md bg-green-100 px-3 py-2 text-sm font-semibold text-green-700 dark:bg-green-900/50 dark:text-green-200">
                        {{ strtoupper($configuration['method'] ?? 'POST') }}
                    </span>
                </div>
            </div>
            @if(isset($configuration['verify_ssl']) && $configuration['verify_ssl'])
                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>{{ __('filament-signal::signal.fields.ssl_verified') }}</span>
                </div>
            @endif
        </div>
    @endif

    {{-- Payload Configuration --}}
    @if(isset($configuration['payload_config']))
        @php
            $payloadConfig = $configuration['payload_config'];
            $includeFields = $payloadConfig['include_fields'] ?? [];
            $relationFields = $payloadConfig['relation_fields'] ?? [];
        @endphp
        <div class="space-y-4">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                {{ __('filament-signal::signal.fields.payload_configuration') }}
            </h4>

            {{-- Included Fields --}}
            @if(!empty($includeFields))
                <div>
                    <div class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-300">
                        {{ __('filament-signal::signal.fields.included_fields') }}
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($includeFields as $field)
                            <span
                                class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/50 dark:text-blue-200">
                                {{ $field }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Relations --}}
            @if(!empty($relationFields))
                <div class="space-y-3">
                    @foreach($relationFields as $relationKey => $fields)
                        @if(!empty($fields))
                            <div>
                                <div class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-300">
                                    {{ __('filament-signal::signal.fields.relations') }}
                                </div>
                                <div class="mb-1 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $relationKey }}
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($fields as $field)
                                        <span
                                            class="inline-flex items-center rounded-md bg-purple-50 px-2.5 py-1 text-xs font-medium text-purple-700 dark:bg-purple-900/50 dark:text-purple-200">
                                            {{ $field }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Log Action Info --}}
    @if($actionType === 'log' && empty($configuration['payload_config']))
        <div class="mt-4 rounded-lg bg-purple-50/50 p-4 text-sm text-purple-700 dark:bg-purple-900/40 dark:text-purple-200">
            {{ __('filament-signal::signal.helpers.log_configuration') }}
        </div>
    @endif
</div>