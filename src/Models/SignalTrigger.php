<?php

namespace Base33\FilamentSignal\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SignalTrigger extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DISABLED = 'disabled';

    public const MATCH_ALL = 'all';

    public const MATCH_ANY = 'any';

    protected $fillable = [
        'name',
        'event_class',
        'description',
        'status',
        'match_type',
        'filters',
        'metadata',
        'activated_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'metadata' => 'array',
        'activated_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('signal.table_names.triggers', parent::getTable());
    }

    public function actions(): HasMany
    {
        return $this->hasMany(config('signal.models.action', SignalAction::class), 'trigger_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(config('signal.models.action_log', SignalActionLog::class));
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Verifica se il payload passa tutti i filtri configurati
     *
     * @param  array<string, mixed>  $payload
     * @return bool
     */
    public function passesFilters(array $payload): bool
    {
        // Log diretto su file per debug
        try {
            $logFile = base_path('storage/logs/signal-debug.log');
            $logMessage = date('Y-m-d H:i:s') . " - passesFilters called - Trigger ID: {$this->id}, Name: {$this->name}, Has filters: " . (!empty($this->filters) ? 'yes' : 'no') . "\n";
            @file_put_contents($logFile, $logMessage, FILE_APPEND);
        } catch (\Throwable $e) {
            // Ignora errori di scrittura
        }

        \Illuminate\Support\Facades\Log::info("Signal: passesFilters called", [
            'trigger_id' => $this->id,
            'trigger_name' => $this->name,
            'has_filters' => !empty($this->filters),
        ]);

        $filters = $this->filters ?? [];

        // Se non ci sono filtri configurati, passa sempre
        if (empty($filters) || !is_array($filters)) {
            \Illuminate\Support\Facades\Log::info("Signal: No filters configured, passing", [
                'trigger_id' => $this->id,
            ]);
            return true;
        }

        $matchType = $this->match_type ?? self::MATCH_ALL;
        $results = [];

        \Illuminate\Support\Facades\Log::info("Signal: Evaluating filters", [
            'trigger_id' => $this->id,
            'filters_count' => count($filters),
            'match_type' => $matchType,
            'payload_keys' => array_keys($payload),
            'filters' => $filters,
        ]);

        foreach ($filters as $index => $filter) {
            try {
                $logFile = base_path('storage/logs/signal-debug.log');
                $logMessage = date('Y-m-d H:i:s') . " - Processing filter {$index} - Filter: " . json_encode($filter) . "\n";
                @file_put_contents($logFile, $logMessage, FILE_APPEND);
            } catch (\Throwable $e) {
            }

            if (!isset($filter['type']) || !isset($filter['data'])) {
                try {
                    $logFile = base_path('storage/logs/signal-debug.log');
                    $logMessage = date('Y-m-d H:i:s') . " - Filter {$index} skipped - missing type or data\n";
                    @file_put_contents($logFile, $logMessage, FILE_APPEND);
                } catch (\Throwable $e) {
                }
                continue;
            }

            $type = $filter['type'];
            $data = $filter['data'];
            $field = $data['field'] ?? null;
            $value = $data['value'] ?? null;

            try {
                $logFile = base_path('storage/logs/signal-debug.log');
                $logMessage = date('Y-m-d H:i:s') . " - Filter {$index} - Type: {$type}, Field: {$field}, Value: {$value}\n";
                @file_put_contents($logFile, $logMessage, FILE_APPEND);
            } catch (\Throwable $e) {
            }

            if (!$field || $value === null) {
                try {
                    $logFile = base_path('storage/logs/signal-debug.log');
                    $logMessage = date('Y-m-d H:i:s') . " - Filter {$index} skipped - missing field or value\n";
                    @file_put_contents($logFile, $logMessage, FILE_APPEND);
                } catch (\Throwable $e) {
                }
                continue;
            }

            // Ottieni il valore del campo dal payload
            $fieldValue = $this->getFieldValue($payload, $field);

            try {
                $logFile = base_path('storage/logs/signal-debug.log');
                $logMessage = date('Y-m-d H:i:s') . " - Filter {$index} - Field value found: " . ($fieldValue !== null ? json_encode($fieldValue) : 'NULL') . "\n";
                @file_put_contents($logFile, $logMessage, FILE_APPEND);
            } catch (\Throwable $e) {
            }

            \Illuminate\Support\Facades\Log::info("Signal: Filter evaluation", [
                'trigger_id' => $this->id,
                'filter_index' => $index,
                'type' => $type,
                'field' => $field,
                'field_value' => $fieldValue,
                'filter_value' => $value,
                'field_value_type' => gettype($fieldValue),
                'filter_value_type' => gettype($value),
            ]);

            // Valuta il filtro in base al tipo
            $result = $this->evaluateFilter($type, $fieldValue, $value);
            $results[] = $result;

            try {
                $logFile = base_path('storage/logs/signal-debug.log');
                $logMessage = date('Y-m-d H:i:s') . " - Filter {$index} result: " . ($result ? 'PASS' : 'FAIL') . "\n";
                @file_put_contents($logFile, $logMessage, FILE_APPEND);
            } catch (\Throwable $e) {
            }
        }

        // Se non ci sono risultati validi, passa sempre
        if (empty($results)) {
            \Illuminate\Support\Facades\Log::info("Signal: No valid filter results, passing", [
                'trigger_id' => $this->id,
            ]);
            return true;
        }

        // Applica la logica match_type
        $finalResult = match ($matchType) {
            self::MATCH_ANY => in_array(true, $results, true),
            default => !in_array(false, $results, true),
        };

        try {
            $logFile = base_path('storage/logs/signal-debug.log');
            $logMessage = date('Y-m-d H:i:s') . " - Final result: " . ($finalResult ? 'PASS' : 'FAIL') . " (Match type: {$matchType}, Results: " . json_encode($results) . ")\n";
            @file_put_contents($logFile, $logMessage, FILE_APPEND);
        } catch (\Throwable $e) {
        }

        return $finalResult;
    }

    /**
     * Ottiene il valore di un campo dal payload, supportando percorsi annidati
     *
     * @param  array<string, mixed>  $payload
     * @param  string  $fieldPath  Es: "blog_post.title" o "author.email" o "blog_post.author.email"
     * @return mixed
     */
    protected function getFieldValue(array $payload, string $fieldPath)
    {
        $parts = explode('.', $fieldPath);

        try {
            $logFile = base_path('storage/logs/signal-debug.log');
            $logMessage = date('Y-m-d H:i:s') . " - Getting field value - Field: {$fieldPath}, Payload keys: " . implode(', ', array_keys($payload)) . "\n";

            // Log anche il contenuto di ogni modello principale per debug
            foreach ($payload as $key => $value) {
                if ($key !== 'event' && is_array($value)) {
                    $modelKeys = array_keys($value);
                    $logMessage .= "  - {$key} keys: " . implode(', ', array_slice($modelKeys, 0, 20)) . (count($modelKeys) > 20 ? '...' : '') . "\n";
                }
            }

            @file_put_contents($logFile, $logMessage, FILE_APPEND);
        } catch (\Throwable $e) {
        }

        // Prova prima il percorso diretto (es: "blog_post.title")
        $value = $payload;
        $found = true;

        foreach ($parts as $part) {
            if (!is_array($value)) {
                $found = false;
                break;
            }

            // Prova prima con la chiave esatta
            if (isset($value[$part])) {
                $value = $value[$part];
                continue;
            }

            // Se non trovato, prova a cercare case-insensitive e con/senza spazi
            $foundKey = null;
            foreach (array_keys($value) as $key) {
                // Normalizza entrambe le chiavi per il confronto (rimuovi spazi, lowercase)
                $normalizedKey = strtolower(str_replace(' ', '', $key));
                $normalizedPart = strtolower(str_replace(' ', '', $part));

                if ($normalizedKey === $normalizedPart) {
                    $foundKey = $key;
                    break;
                }
            }

            if ($foundKey !== null) {
                $value = $value[$foundKey];
                continue;
            }

            $found = false;
            break;
        }

        if ($found) {
            try {
                $logFile = base_path('storage/logs/signal-debug.log');
                $logMessage = date('Y-m-d H:i:s') . " - Field found via direct path: " . json_encode($value) . "\n";
                @file_put_contents($logFile, $logMessage, FILE_APPEND);
            } catch (\Throwable $e) {
            }
            return $value;
        }

        // Se non trovato, prova a cercare dentro tutti i modelli principali del payload
        // Il payload potrebbe avere struttura: ['event' => [...], 'blog_post' => [...], 'author' => [...]]
        // Le relazioni sono annidate dentro i modelli principali (es: blog_post.author.email)
        // Quindi cerchiamo prima dentro ogni modello principale del payload
        // Prima, prova a cercare i campi dentro il modello principale o le relazioni
        // Es: "blog post.author_id" -> cerca dentro $payload['blog post']['blog_author_id']
        // Es: "blog post.author.name" -> cerca dentro $payload['blog post']['author']['name']
        if (count($parts) >= 2) {
            $modelKey = $parts[0];

            // Se ci sono 3+ parti, è una relazione annidata (es: "blog post.author.name")
            if (count($parts) >= 3) {
                $relationName = $parts[1];
                $fieldName = $parts[2];

                // Cerca il modello principale nel payload
                foreach ($payload as $payloadKey => $payloadValue) {
                    if ($payloadKey === 'event') {
                        continue;
                    }

                    $normalizedPayloadKey = strtolower(str_replace(' ', '', $payloadKey));
                    $normalizedModelKey = strtolower(str_replace(' ', '', $modelKey));

                    if ($normalizedPayloadKey === $normalizedModelKey && is_array($payloadValue)) {
                        // Cerca la relazione dentro il modello
                        foreach (array_keys($payloadValue) as $relationKey) {
                            $normalizedRelationKey = strtolower(str_replace([' ', '_'], '', $relationKey));
                            $normalizedRelationName = strtolower(str_replace([' ', '_'], '', $relationName));

                            if ($normalizedRelationKey === $normalizedRelationName && is_array($payloadValue[$relationKey])) {
                                // Cerca il campo dentro la relazione
                                if (isset($payloadValue[$relationKey][$fieldName])) {
                                    try {
                                        $logFile = base_path('storage/logs/signal-debug.log');
                                        $logMessage = date('Y-m-d H:i:s') . " - Field found in nested relation - Model: {$payloadKey}, Relation: {$relationKey}, Field: {$fieldName}, Value: " . json_encode($payloadValue[$relationKey][$fieldName]) . "\n";
                                        @file_put_contents($logFile, $logMessage, FILE_APPEND);
                                    } catch (\Throwable $e) {
                                    }
                                    return $payloadValue[$relationKey][$fieldName];
                                }

                                // Prova anche con normalizzazione
                                foreach (array_keys($payloadValue[$relationKey]) as $relFieldKey) {
                                    $normalizedRelFieldKey = strtolower(str_replace([' ', '_'], '', $relFieldKey));
                                    $normalizedFieldName = strtolower(str_replace([' ', '_'], '', $fieldName));
                                    if ($normalizedRelFieldKey === $normalizedFieldName) {
                                        try {
                                            $logFile = base_path('storage/logs/signal-debug.log');
                                            $logMessage = date('Y-m-d H:i:s') . " - Field found in nested relation (normalized) - Model: {$payloadKey}, Relation: {$relationKey}, Field: {$relFieldKey}, Value: " . json_encode($payloadValue[$relationKey][$relFieldKey]) . "\n";
                                            @file_put_contents($logFile, $logMessage, FILE_APPEND);
                                        } catch (\Throwable $e) {
                                        }
                                        return $payloadValue[$relationKey][$relFieldKey];
                                    }
                                }
                            }
                        }
                    }
                }

                // Se non trovato, continua con la ricerca normale per campi a 2 parti
            }

            // Gestisce campi a 2 parti (modello.campo)
            $fieldName = $parts[1];

            // Cerca il modello principale nel payload (gestendo spazi)
            foreach ($payload as $payloadKey => $payloadValue) {
                if ($payloadKey === 'event') {
                    continue;
                }

                // Normalizza per gestire spazi
                $normalizedPayloadKey = strtolower(str_replace(' ', '', $payloadKey));
                $normalizedModelKey = strtolower(str_replace(' ', '', $modelKey));

                if ($normalizedPayloadKey === $normalizedModelKey && is_array($payloadValue)) {
                    // Cerca il campo dentro il modello con diversi formati possibili
                    // Il campo potrebbe essere: author_id, blog_post_author_id, blog_author_id, ecc.
                    $modelKeySnake = str_replace(' ', '_', $modelKey);
                    $modelKeyParts = explode('_', $modelKeySnake);
                    $lastModelPart = end($modelKeyParts); // Es: "post" da "blog_post"

                    $possibleFieldNames = [
                        $fieldName, // Es: author_id
                        $modelKeySnake . '_' . $fieldName, // Es: blog_post_author_id
                        $lastModelPart . '_' . $fieldName, // Es: post_author_id
                        $modelKeySnake . '_' . str_replace('_id', '', $fieldName) . '_id', // Es: blog_post_author_id
                        $lastModelPart . '_' . str_replace('_id', '', $fieldName) . '_id', // Es: post_author_id
                    ];

                    // Rimuovi duplicati
                    $possibleFieldNames = array_unique($possibleFieldNames);

                    foreach ($possibleFieldNames as $possibleFieldName) {
                        if (isset($payloadValue[$possibleFieldName])) {
                            try {
                                $logFile = base_path('storage/logs/signal-debug.log');
                                $logMessage = date('Y-m-d H:i:s') . " - Field found in model - Model: {$payloadKey}, Field: {$possibleFieldName}, Value: " . json_encode($payloadValue[$possibleFieldName]) . "\n";
                                @file_put_contents($logFile, $logMessage, FILE_APPEND);
                            } catch (\Throwable $e) {
                            }
                            return $payloadValue[$possibleFieldName];
                        }
                    }

                    // Prova anche con normalizzazione del campo (case-insensitive, spazi)
                    foreach (array_keys($payloadValue) as $modelFieldKey) {
                        $normalizedModelFieldKey = strtolower(str_replace([' ', '_'], '', $modelFieldKey));
                        foreach ($possibleFieldNames as $possibleFieldName) {
                            $normalizedFieldName = strtolower(str_replace([' ', '_'], '', $possibleFieldName));
                            if ($normalizedModelFieldKey === $normalizedFieldName) {
                                try {
                                    $logFile = base_path('storage/logs/signal-debug.log');
                                    $logMessage = date('Y-m-d H:i:s') . " - Field found in model (normalized) - Model: {$payloadKey}, Field: {$modelFieldKey}, Value: " . json_encode($payloadValue[$modelFieldKey]) . "\n";
                                    @file_put_contents($logFile, $logMessage, FILE_APPEND);
                                } catch (\Throwable $e) {
                                }
                                return $payloadValue[$modelFieldKey];
                            }
                        }
                    }

                    // Se il campo termina con _id, prova a cercare tutti i campi che terminano con lo stesso suffisso
                    // Es: author_id -> cerca blog_author_id, post_author_id, ecc.
                    // Questo è il modo più semplice e diretto: usa i campi effettivi del payload
                    if (str_ends_with($fieldName, '_id')) {
                        $suffix = $fieldName; // Es: author_id
                        try {
                            $logFile = base_path('storage/logs/signal-debug.log');
                            $logMessage = date('Y-m-d H:i:s') . " - Searching by suffix: {$suffix} in model: {$payloadKey}\n";
                            @file_put_contents($logFile, $logMessage, FILE_APPEND);
                        } catch (\Throwable $e) {
                        }

                        foreach (array_keys($payloadValue) as $modelFieldKey) {
                            if (str_ends_with($modelFieldKey, $suffix)) {
                                try {
                                    $logFile = base_path('storage/logs/signal-debug.log');
                                    $logMessage = date('Y-m-d H:i:s') . " - Field found by suffix match - Model: {$payloadKey}, Field: {$modelFieldKey}, Value: " . json_encode($payloadValue[$modelFieldKey]) . "\n";
                                    @file_put_contents($logFile, $logMessage, FILE_APPEND);
                                } catch (\Throwable $e) {
                                }
                                return $payloadValue[$modelFieldKey];
                            }
                        }
                    }
                }
            }
        }

        foreach ($payload as $key => $modelData) {
            // Salta le chiavi che non sono modelli (es: 'event')
            if ($key === 'event') {
                continue;
            }

            if (!is_array($modelData)) {
                continue;
            }

            // Prova a cercare il campo dentro questo modello
            // Se il campo è "author.email", cerchiamo in modelData['author']['email']
            // Se il campo è "blog post.title" cerchiamo in modelData['title']
            $value = $modelData;
            $found = true;

            // Se il campo inizia con il nome del modello (normalizzato), rimuovilo
            $searchParts = $parts;
            $normalizedKey = strtolower(str_replace([' ', '_'], '', $key));
            $normalizedFirstPart = strtolower(str_replace([' ', '_'], '', $parts[0] ?? ''));

            if (count($parts) > 1 && $normalizedFirstPart === $normalizedKey) {
                $searchParts = array_slice($parts, 1);
            }

            foreach ($searchParts as $part) {
                if (!is_array($value)) {
                    $found = false;
                    break;
                }

                // Prova prima con la chiave esatta
                if (isset($value[$part])) {
                    $value = $value[$part];
                    continue;
                }

                // Se non trovato, prova a cercare case-insensitive e con/senza spazi/underscore
                $foundKey = null;
                foreach (array_keys($value) as $valueKey) {
                    $normalizedValueKey = strtolower(str_replace([' ', '_'], '', $valueKey));
                    $normalizedPart = strtolower(str_replace([' ', '_'], '', $part));

                    if ($normalizedValueKey === $normalizedPart) {
                        $foundKey = $valueKey;
                        break;
                    }
                }

                if ($foundKey !== null) {
                    $value = $value[$foundKey];
                    continue;
                }

                $found = false;
                break;
            }

            if ($found) {
                try {
                    $logFile = base_path('storage/logs/signal-debug.log');
                    $logMessage = date('Y-m-d H:i:s') . " - Field found via model search - Model: {$key}, Value: " . json_encode($value) . "\n";
                    @file_put_contents($logFile, $logMessage, FILE_APPEND);
                } catch (\Throwable $e) {
                }
                return $value;
            }
        }

        // Se ancora non trovato, prova a cercare ricorsivamente nel payload
        $recursiveValue = $this->findValueRecursive($payload, $parts);

        try {
            $logFile = base_path('storage/logs/signal-debug.log');
            $logMessage = date('Y-m-d H:i:s') . " - Field search result: " . ($recursiveValue !== null ? json_encode($recursiveValue) : 'NOT FOUND') . "\n";
            @file_put_contents($logFile, $logMessage, FILE_APPEND);
        } catch (\Throwable $e) {
        }

        return $recursiveValue;
    }

    /**
     * Cerca ricorsivamente un valore nel payload usando i percorsi
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $parts
     * @return mixed
     */
    protected function findValueRecursive(array $data, array $parts): mixed
    {
        if (empty($parts)) {
            return null;
        }

        $firstPart = array_shift($parts);

        if (!isset($data[$firstPart])) {
            return null;
        }

        $value = $data[$firstPart];

        if (empty($parts)) {
            return $value;
        }

        if (!is_array($value)) {
            return null;
        }

        return $this->findValueRecursive($value, $parts);
    }

    /**
     * Valuta un singolo filtro
     *
     * @param  string  $type
     * @param  mixed  $fieldValue
     * @param  mixed  $filterValue
     * @return bool
     */
    protected function evaluateFilter(string $type, $fieldValue, $filterValue): bool
    {
        // Se il campo non esiste nel payload, il filtro fallisce
        if ($fieldValue === null) {
            return false;
        }

        return match ($type) {
            'equals' => $this->compareValues($fieldValue, $filterValue) === 0,
            'not_equals' => $this->compareValues($fieldValue, $filterValue) !== 0,
            'contains' => $this->stringContains($fieldValue, $filterValue),
            'not_contains' => !$this->stringContains($fieldValue, $filterValue),
            'greater_than' => $this->compareValues($fieldValue, $filterValue) > 0,
            'greater_than_or_equal' => $this->compareValues($fieldValue, $filterValue) >= 0,
            'less_than' => $this->compareValues($fieldValue, $filterValue) < 0,
            'less_than_or_equal' => $this->compareValues($fieldValue, $filterValue) <= 0,
            'in' => $this->isIn($fieldValue, $filterValue),
            'not_in' => !$this->isIn($fieldValue, $filterValue),
            default => false,
        };
    }

    /**
     * Confronta due valori (supporta numeri e stringhe)
     *
     * @param  mixed  $a
     * @param  mixed  $b
     * @return int  -1 se $a < $b, 0 se $a == $b, 1 se $a > $b
     */
    protected function compareValues($a, $b): int
    {
        // Prova a convertire in numeri se possibile
        $aNumeric = is_numeric($a) ? (float) $a : null;
        $bNumeric = is_numeric($b) ? (float) $b : null;

        if ($aNumeric !== null && $bNumeric !== null) {
            return $aNumeric <=> $bNumeric;
        }

        // Altrimenti confronta come stringhe
        return strcmp((string) $a, (string) $b);
    }

    /**
     * Verifica se una stringa contiene un'altra (case-insensitive)
     * Se il valore contiene virgole, tratta come lista e verifica se il valore è nella lista
     *
     * @param  mixed  $haystack
     * @param  mixed  $needle
     * @return bool
     */
    protected function stringContains($haystack, $needle): bool
    {
        $needleString = (string) $needle;
        
        // Se il valore contiene virgole, tratta come lista e verifica se il valore è nella lista
        if (str_contains($needleString, ',')) {
            return $this->isIn($haystack, $needleString);
        }
        
        // Altrimenti, verifica se la stringa contiene la sottostringa
        return stripos((string) $haystack, $needleString) !== false;
    }

    /**
     * Verifica se un valore è in una lista (per filtri "in" e "not_in")
     *
     * @param  mixed  $value
     * @param  string  $valuesString  Stringa separata da virgole o newline
     * @return bool
     */
    protected function isIn($value, string $valuesString): bool
    {
        // Supporta sia virgole che newline come separatori
        $values = preg_split('/[,\n\r]+/', trim($valuesString));
        $values = array_map('trim', $values);
        $values = array_filter($values);

        return in_array((string) $value, $values, true);
    }
}
