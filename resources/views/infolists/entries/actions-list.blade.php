@php
    $actions = collect($getState() ?? []);
@endphp
<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    <div class="space-y-6">
        @forelse ($actions as $index => $action)
            @php
                $configuration = $action['configuration'] ?? [];
                $payloadConfig = $configuration['payload_config'] ?? [];
                $includeFields = $payloadConfig['include_fields'] ?? [];
                $relationFields = $payloadConfig['relation_fields'] ?? [];
                $actionType = strtoupper($action['action_type'] ?? '');
                $order = $action['execution_order'] ?? ($index + 1);
                $isWebhook = $action['action_type'] === 'webhook';

                // Processa include_fields: CheckboxList restituisce array con chiavi = valori selezionati
                $processedIncludeFields = [];
                if (is_array($includeFields)) {
                    foreach ($includeFields as $key => $value) {
                        // Se è array associativo dove chiave = valore (CheckboxList standard)
                        if (is_string($key) && is_string($value) && $key === $value) {
                            $processedIncludeFields[] = $key;
                        }
                        // Se è array associativo con valori booleani
                        elseif (is_string($key) && ($value === true || $value === '1' || $value === 1)) {
                            $processedIncludeFields[] = $key;
                        }
                        // Se è array numerico (valori diretti)
                        elseif (is_numeric($key) && !empty($value) && is_string($value)) {
                            $processedIncludeFields[] = $value;
                        }
                    }
                }
                $includeFields = array_values(array_unique(array_filter($processedIncludeFields, fn($field) => !empty($field) && is_string($field))));

                // Processa relation_fields: struttura è relationKey => [field1 => field1, field2 => field2, ...]
                $filteredRelationFields = [];
                if (is_array($relationFields)) {
                    foreach ($relationFields as $relationKey => $fields) {
                        if (!is_array($fields)) {
                            continue;
                        }

                        $selectedFields = [];
                        foreach ($fields as $fieldKey => $fieldValue) {
                            // Se è array associativo dove chiave = valore (CheckboxList standard)
                            if (is_string($fieldKey) && is_string($fieldValue) && $fieldKey === $fieldValue) {
                                $selectedFields[] = $fieldKey;
                            }
                            // Se è array associativo con valori booleani
                            elseif (is_string($fieldKey) && ($fieldValue === true || $fieldValue === '1' || $fieldValue === 1)) {
                                $selectedFields[] = $fieldKey;
                            }
                            // Se è array numerico (valori diretti)
                            elseif (is_numeric($fieldKey) && !empty($fieldValue) && is_string($fieldValue)) {
                                $selectedFields[] = $fieldValue;
                            }
                        }

                        if (!empty($selectedFields)) {
                            $filteredRelationFields[$relationKey] = array_values(array_unique($selectedFields));
                        }
                    }
                }
                $relationFields = $filteredRelationFields;
            @endphp

            <div class="relative">
                <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    {{-- Header card --}}
                    <div class="mb-6">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex-1">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                                    <x-filament::badge :color="match($action['action_type']) { 'webhook' => 'info', 'log' => 'gray', default => 'secondary' }" size="sm" class="self-start">
                                        {{ $actionType }}
                                    </x-filament::badge>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $action['name'] ?? '—' }}
                                    </h3>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                <x-filament::icon icon="heroicon-o-clock" class="w-4 h-4 shrink-0" />
                                <span>{{ __('filament-signal::signal.fields.order') }}: <span class="font-semibold">{{ $order }}</span></span>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        {{-- Left: Webhook Configuration (solo per webhook) --}}
                        @if($isWebhook)
                            <div class="space-y-4">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ __('filament-signal::signal.fields.endpoint_destination') }}
                                </h4>

                                <div class="space-y-3 text-sm">
                                    <div>
                                        <div class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                                            {{ __('filament-signal::signal.fields.endpoint_url') }}
                                        </div>
                                        <div class="flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-900/50">
                                            <x-filament::icon icon="heroicon-o-link" class="w-4 h-4 shrink-0 text-gray-400" />
                                            <span class="break-all font-mono text-xs text-gray-700 dark:text-gray-300">{{ $configuration['url'] ?? '—' }}</span>
                                        </div>
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
                                </div>
                            </div>
                        @endif

                        {{-- Right: Payload Data Mapping (sempre a destra) --}}
                        <div class="space-y-4 {{ !$isWebhook ? 'md:col-start-2' : '' }}">
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
                                                // Formatta la chiave della relazione (es: "loan_unit_id" -> "Loan Unit")
                                                $relationParts = explode('_', $relationKey);
                                                $relationName = ucwords(implode(' ', array_slice($relationParts, 0, -1)));
                                                // Assicurati che ci siano campi selezionati
                                                $selectedFields = is_array($fields) ? array_filter($fields, fn($field) => !empty($field)) : [];
                                            @endphp
                                            @if(!empty($selectedFields))
                                                <div class="p-3 border border-gray-200 rounded-lg bg-gray-50 dark:border-gray-700 dark:bg-gray-900/50">
                                                    <div class="mb-2 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                                        {{ $relationName }}
                                                    </div>
                                                    <div class="flex flex-wrap gap-1.5">
                                                        @foreach($selectedFields as $field)
                                                            <span class="inline-flex items-center rounded bg-white px-2 py-0.5 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                                {{ str_replace('.', ' → ', $field) }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
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
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    {{ __('filament-signal::signal.fields.end_of_pipeline') }}
                </div>
            @endif
        @empty
            <div class="text-sm italic text-gray-400 dark:text-gray-500">—</div>
        @endforelse
    </div>
</x-dynamic-component>
