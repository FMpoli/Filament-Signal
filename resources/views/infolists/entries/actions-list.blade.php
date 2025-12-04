@php
    $actions = collect($getState() ?? []);
@endphp
<div class="text-primary-600">123</div>
<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    <div class="space-y-6">
        @if($actions->isNotEmpty())
            <div class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
                <x-filament::icon icon="heroicon-o-bolt" class="w-5 h-5" />
                <span>{{ __('filament-signal::signal.fields.execution_pipeline') }}</span>
            </div>
        @endif

        @forelse ($actions as $index => $action)
            @php
                $configuration = $action['configuration'] ?? [];
                $payloadConfig = $configuration['payload_config'] ?? [];
                $includeFields = $payloadConfig['include_fields'] ?? [];
                $relationFields = $payloadConfig['relation_fields'] ?? [];
                $actionType = strtoupper($action['action_type'] ?? '');
                $order = $action['execution_order'] ?? ($index + 1);
            @endphp

            <div class="relative">
                {{-- Numero step cerchiato --}}
                <div class="absolute top-0 flex items-center justify-center w-8 h-8 text-sm font-bold text-gray-700 bg-white border-2 border-gray-300 rounded-full -left-8 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    {{ $order }}
                </div>

                {{-- Linea verticale tratteggiata --}}
                @if(!$loop->last)
                    <div class="absolute -left-5 top-8 h-full w-0.5 border-l-2 border-dashed border-gray-300 dark:border-gray-600"></div>
                @endif

                <div class="p-6 ml-8 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    {{-- Header card --}}
                    <div class="flex items-start justify-between mb-6">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $action['name'] ?? '—' }}
                                </h3>
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    {{ $actionType }}
                                </span>
                            </div>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('filament-signal::signal.fields.execution_order') }}: <span class="font-semibold">{{ $order }}</span>
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        {{-- Left: Webhook Configuration --}}
                        @if($action['action_type'] === 'webhook')
                            <div class="space-y-4">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ __('filament-signal::signal.fields.endpoint_destination') }}
                                </h4>

                                <div class="space-y-3 text-sm">
                                    <div class="flex items-center gap-2">
                                        <x-filament::icon icon="heroicon-o-link" class="w-4 h-4 text-gray-400" />
                                        <span class="text-gray-600 dark:text-gray-300">{{ $configuration['url'] ?? '—' }}</span>
                                    </div>

                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('filament-signal::signal.fields.http_method') }}:</span>
                                        <span class="ml-2 text-gray-600 dark:text-gray-400">{{ strtoupper($configuration['method'] ?? 'POST') }}</span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('filament-signal::signal.fields.security') }}:</span>
                                        @if($configuration['verify_ssl'] ?? true)
                                            <x-filament::icon icon="heroicon-o-check-circle" class="w-4 h-4 text-green-500" />
                                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('filament-signal::signal.fields.ssl_on') }}</span>
                                        @else
                                            <x-filament::icon icon="heroicon-o-x-circle" class="w-4 h-4 text-red-500" />
                                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('filament-signal::signal.fields.ssl_off') }}</span>
                                        @endif
                                    </div>

                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('filament-signal::signal.fields.signing_secret') }}:</span>
                                        <span class="inline-flex items-center gap-1 ml-2 text-sm text-gray-500 dark:text-gray-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                            {{ __('filament-signal::signal.fields.auto_generated') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Right: Payload Data Mapping --}}
                        <div class="space-y-4">
                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-circle-stack" class="w-5 h-5 text-gray-400" />
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ __('filament-signal::signal.fields.payload_data_mapping') }}
                                </h4>
                            </div>

                            {{-- Essential Fields --}}
                            @if(!empty($includeFields))
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <x-filament::icon icon="heroicon-o-check-circle" class="w-4 h-4 text-green-500" />
                                        <span class="text-xs font-semibold tracking-wide text-gray-700 uppercase dark:text-gray-300">
                                            {{ __('filament-signal::signal.fields.essential_fields') }}
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($includeFields as $field)
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                {{ str_replace('.', ' → ', $field) }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Relations --}}
                            @if(!empty($relationFields))
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <x-filament::icon icon="heroicon-o-link" class="w-4 h-4 text-gray-400" />
                                        <span class="text-xs font-semibold tracking-wide text-gray-700 uppercase dark:text-gray-300">
                                            {{ __('filament-signal::signal.fields.relations') }}
                                        </span>
                                    </div>
                                    <div class="space-y-3">
                                        @foreach($relationFields as $relationKey => $fields)
                                            @php
                                                // Formatta la chiave della relazione (es: "loan_unit_id" -> "Loan -- Unit")
                                                $relationParts = explode('_', $relationKey);
                                                $relationName = ucwords(implode(' ', array_slice($relationParts, 0, -1)));
                                                $relationType = ucfirst(end($relationParts));
                                            @endphp
                                            <div class="p-3 border border-gray-200 rounded-lg bg-gray-50 dark:border-gray-700 dark:bg-gray-900/50">
                                                <div class="mb-2 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                                    {{ $relationName }} -- {{ $relationType }}
                                                </div>
                                                <div class="flex flex-wrap gap-1.5">
                                                    @foreach($fields as $field)
                                                        <span class="inline-flex items-center rounded bg-white px-2 py-0.5 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                            {{ str_replace('.', ' → ', $field) }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if(empty($includeFields) && empty($relationFields))
                                <div class="text-sm italic text-gray-400 dark:text-gray-500">
                                    {{ __('filament-signal::signal.fields.no_payload_configuration') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if($loop->last)
                <div class="ml-8 text-xs font-medium text-gray-500 dark:text-gray-400">
                    {{ __('filament-signal::signal.fields.end_of_pipeline') }}
                </div>
            @endif
        @empty
            <div class="text-sm italic text-gray-400 dark:text-gray-500">—</div>
        @endforelse
    </div>
</x-dynamic-component>
