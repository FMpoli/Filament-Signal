@php
    // In Filament 4, ViewField passa lo stato tramite $component->getState()
    $state = isset($component) && method_exists($component, 'getState') ? $component->getState() : null;
    $label = isset($component) && method_exists($component, 'getLabel') ? $component->getLabel() : null;

    $formatted = null;

    if (!blank($state)) {
        // Se è già una stringa JSON, prova a decodificarla
        if (is_string($state)) {
            $decoded = json_decode($state, true);
            // Se la decodifica ha successo, usa l'array/oggetto decodificato
            if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
                $state = $decoded;
            }
        }

        // Formatta come JSON leggibile
        if (is_array($state) || is_object($state)) {
            $formatted = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            $formatted = $state;
        }
    }
@endphp

<div class="space-y-2">
    @if (filled($label))
        <div class="text-sm font-semibold text-gray-600 dark:text-gray-300">
            {{ $label }}
        </div>
    @endif

    @if (blank($formatted))
        <div class="text-sm italic text-gray-400 dark:text-gray-500">—</div>
    @else
        <pre class="max-h-64 overflow-auto rounded-lg bg-gray-950/5 p-3 text-xs font-mono text-gray-900 dark:bg-white/5 dark:text-gray-100 whitespace-pre-wrap">{{ $formatted }}</pre>
    @endif
</div>

