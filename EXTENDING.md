# Estendere Signal con nuovi Action Types

Signal è progettato per essere facilmente estendibile. Puoi creare plugin esterni che aggiungono nuovi tipi di azioni (ad esempio Telegram, Slack, Discord, ecc.).

## Come aggiungere un nuovo Action Type

### 1. Crea il tuo Action Handler

Il tuo handler deve implementare l'interfaccia `SignalActionHandler`:

```php
<?php

namespace YourPlugin\Actions;

use Base33\FilamentSignal\Contracts\SignalActionHandler;
use Base33\FilamentSignal\Models\SignalAction;
use Base33\FilamentSignal\Models\SignalActionLog;

class TelegramActionHandler implements SignalActionHandler
{
    public function handle(SignalAction $action, array $payload, string $eventClass, ?SignalActionLog $log = null): ?array
    {
        $configuration = $action->configuration ?? [];
        $botToken = Arr::get($configuration, 'bot_token');
        $chatId = Arr::get($configuration, 'chat_id');
        $message = Arr::get($configuration, 'message');
        
        // Implementa la tua logica qui
        // ...
        
        return [
            'sent' => true,
            'chat_id' => $chatId,
        ];
    }
}
```

### 2. Registra il tuo Handler

Nel Service Provider del tuo plugin, aggiungi il tuo handler alla configurazione:

```php
<?php

namespace YourPlugin\Providers;

use Illuminate\Support\ServiceProvider;

class YourPluginServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Registra il tuo action handler
        config([
            'signal.action_handlers.telegram' => \YourPlugin\Actions\TelegramActionHandler::class,
        ]);
    }
}
```

Oppure, se preferisci pubblicare il file di configurazione:

```bash
php artisan vendor:publish --tag=signal-config
```

E poi modifica `config/signal.php`:

```php
'action_handlers' => [
    'log' => LogActionHandler::class,
    'webhook' => WebhookActionHandler::class,
    'telegram' => \YourPlugin\Actions\TelegramActionHandler::class, // Il tuo handler
],
```

### 3. Aggiungi le traduzioni (opzionale)

Se vuoi che il nome del tuo action type appaia tradotto nell'interfaccia, aggiungi le traduzioni:

**Inglese** (`resources/lang/en/signal.php`):
```php
'action_types' => [
    'log' => 'Log',
    'webhook' => 'Webhook',
    'telegram' => 'Telegram', // Il tuo action type
],
```

**Italiano** (`resources/lang/it/signal.php`):
```php
'action_types' => [
    'log' => 'Log',
    'webhook' => 'Webhook',
    'telegram' => 'Telegram', // Il tuo action type
],
```

### 4. Personalizza il form (opzionale)

Se vuoi aggiungere campi specifici nel form quando viene selezionato il tuo action type, puoi estendere `SignalTriggerResource`:

```php
use Base33\FilamentSignal\Filament\Resources\SignalTriggerResource;

// Nel tuo Service Provider
public function boot(): void
{
    SignalTriggerResource::configureUsing(function ($resource) {
        // Aggiungi campi personalizzati per il tuo action type
        $resource->form(function ($form) {
            return $form->schema([
                // ... campi esistenti ...
                
                Forms\Components\TextInput::make('configuration.bot_token')
                    ->label('Telegram Bot Token')
                    ->visible(fn(Get $get): bool => $get('action_type') === 'telegram')
                    ->required(fn(Get $get): bool => $get('action_type') === 'telegram'),
                    
                Forms\Components\TextInput::make('configuration.chat_id')
                    ->label('Chat ID')
                    ->visible(fn(Get $get): bool => $get('action_type') === 'telegram')
                    ->required(fn(Get $get): bool => $get('action_type') === 'telegram'),
            ]);
        });
    });
}
```

## Esempio completo: Plugin Telegram

Ecco un esempio completo di come creare un plugin Telegram per Signal:

### Struttura del plugin

```
your-plugin/
├── src/
│   ├── Actions/
│   │   └── TelegramActionHandler.php
│   └── Providers/
│       └── TelegramServiceProvider.php
├── config/
│   └── telegram.php
└── composer.json
```

### TelegramActionHandler.php

```php
<?php

namespace YourPlugin\Actions;

use Base33\FilamentSignal\Contracts\SignalActionHandler;
use Base33\FilamentSignal\Models\SignalAction;
use Base33\FilamentSignal\Models\SignalActionLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class TelegramActionHandler implements SignalActionHandler
{
    public function handle(SignalAction $action, array $payload, string $eventClass, ?SignalActionLog $log = null): ?array
    {
        $configuration = $action->configuration ?? [];
        $botToken = Arr::get($configuration, 'bot_token');
        $chatId = Arr::get($configuration, 'chat_id');
        $message = Arr::get($configuration, 'message', 'Event triggered: ' . $eventClass);
        
        if (blank($botToken) || blank($chatId)) {
            throw new InvalidArgumentException("Signal action [{$action->id}] is missing Telegram configuration.");
        }
        
        // Sostituisci i placeholder nel messaggio con i dati del payload
        $formattedMessage = $this->formatMessage($message, $payload, $eventClass);
        
        $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $formattedMessage,
            'parse_mode' => 'HTML',
        ]);
        
        if ($response->failed()) {
            throw new \Exception("Telegram API error: " . $response->body());
        }
        
        return [
            'sent' => true,
            'chat_id' => $chatId,
            'message_id' => $response->json('result.message_id'),
        ];
    }
    
    protected function formatMessage(string $message, array $payload, string $eventClass): string
    {
        // Sostituisci i placeholder
        $message = str_replace('{event}', $eventClass, $message);
        $message = str_replace('{payload}', json_encode($payload, JSON_PRETTY_PRINT), $message);
        
        return $message;
    }
}
```

### TelegramServiceProvider.php

```php
<?php

namespace YourPlugin\Providers;

use Illuminate\Support\ServiceProvider;

class TelegramServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Registra l'action handler
        config([
            'signal.action_handlers.telegram' => \YourPlugin\Actions\TelegramActionHandler::class,
        ]);
    }
}
```

## Note importanti

1. **Il log viene creato automaticamente**: `SignalActionExecutor` crea sempre un log prima di chiamare il tuo handler. Il log viene passato come parametro `$log` e puoi aggiornarlo se necessario.

2. **Gestione errori**: Se il tuo handler lancia un'eccezione, il log verrà automaticamente marcato come "failed" e l'errore verrà registrato.

3. **Payload**: Il payload contiene i dati dell'evento già configurati secondo le impostazioni di "Payload Configuration" se presenti.

4. **Response**: Il valore di ritorno del metodo `handle()` viene salvato nel campo `response` del log e può essere utilizzato per debugging o per mostrare informazioni all'utente.

## Esempi di Action Types possibili

- **Telegram**: Invia messaggi su Telegram
- **Slack**: Invia messaggi su Slack
- **Discord**: Invia messaggi su Discord
- **SMS**: Invia SMS tramite servizi come Twilio
- **Push Notification**: Invia notifiche push
- **Database**: Salva dati in una tabella personalizzata
- **File**: Scrive file o log personalizzati
- **Queue**: Aggiunge job personalizzati a code Laravel

