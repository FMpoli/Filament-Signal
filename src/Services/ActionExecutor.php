<?php

namespace Voodflow\Voodflow\Services;

use Voodflow\Voodflow\Contracts\SignalActionHandler;
use Voodflow\Voodflow\Models\SignalAction;
use Voodflow\Voodflow\Models\SignalActionLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class ActionExecutor
{
    /**
     * @param  array<string, class-string<SignalActionHandler>>  $handlers
     */
    public function __construct(
        protected array $handlers = []
    ) {
        $this->handlers = $handlers ?: config('voodflow.action_handlers', []);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(SignalAction $action, array $payload, string $eventClass): void
    {
        $log = $this->createLog($action, $eventClass, $payload);

        try {
            Log::info("Signal: Executing action [{$action->id}] of type [{$action->action_type}]", [
                'action_id' => $action->id,
                'action_type' => $action->action_type,
                'event_class' => $eventClass,
            ]);

            $handler = $this->resolveHandler($action->action_type);

            $response = $handler->handle($action, $payload, $eventClass, $log);

            Log::info("Signal: Action [{$action->id}] executed successfully", [
                'action_id' => $action->id,
                'response' => $response,
            ]);

            // Se è un'action di tipo 'log', traccia sempre i success
            // Per altre action types, traccia i success solo se configurato
            $shouldLogSuccess = $this->shouldLogSuccess($action);

            if ($shouldLogSuccess) {
                // Aggiorna il log con status e response, preservando il payload già aggiornato dal handler
                $log->refresh(); // Ricarica il log per avere l'ultima versione (in caso il handler l'abbia aggiornato)
                $log->update([
                    'status' => 'success',
                    'response' => $response,
                    'executed_at' => now(),
                ]);
            } else {
                // Se non dobbiamo tracciare i success, elimina il log
                // IMPORTANTE: Gli errori vengono sempre tracciati nel blocco catch
                $log->delete();
            }
        } catch (Throwable $exception) {
            Log::error("Signal: Action [{$action->id}] failed", [
                'action_id' => $action->id,
                'action_type' => $action->action_type,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $log->update([
                'status' => 'failed',
                'message' => $exception->getMessage(),
                'executed_at' => now(),
            ]);
        }
    }

    protected function createLog(SignalAction $action, string $eventClass, array $payload): SignalActionLog
    {
        return $action->logs()->create([
            'trigger_id' => $action->trigger_id,
            'event_class' => $eventClass,
            'status' => 'pending',
            'payload' => $payload,
        ]);
    }

    protected function resolveHandler(string $type): SignalActionHandler
    {
        $class = Arr::get($this->handlers, $type);

        if (!$class) {
            throw new InvalidArgumentException(sprintf(
                'No Signal handler registered for action type [%s].',
                $type
            ));
        }

        $handler = App::make($class);

        if (!$handler instanceof SignalActionHandler) {
            throw new InvalidArgumentException(sprintf(
                'Handler [%s] must implement %s.',
                $class,
                SignalActionHandler::class
            ));
        }

        return $handler;
    }

    /**
     * Determina se dobbiamo tracciare i success per questa action.
     * Gli errori vengono sempre tracciati.
     *
     * Regole:
     * - Se action_type === 'log': traccia sempre i success (non controlla log_success)
     * - Se action_type !== 'log': traccia i success solo se log_success è true
     */
    protected function shouldLogSuccess(SignalAction $action): bool
    {
        // Se è un'action di tipo 'log', traccia sempre i success
        if ($action->action_type === 'log') {
            return true;
        }

        // Per altre action types, controlla la configurazione log_success
        $configuration = $action->configuration ?? [];

        return Arr::get($configuration, 'log_success', false);
    }
}
