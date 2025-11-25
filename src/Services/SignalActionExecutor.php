<?php

namespace Base33\FilamentSignal\Services;

use Base33\FilamentSignal\Contracts\SignalActionHandler;
use Base33\FilamentSignal\Models\SignalAction;
use Base33\FilamentSignal\Models\SignalActionLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;
use Throwable;

class SignalActionExecutor
{
    /**
     * @param  array<string, class-string<SignalActionHandler>>  $handlers
     */
    public function __construct(
        protected array $handlers = []
    ) {
        $this->handlers = $handlers ?: config('signal.action_handlers', []);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(SignalAction $action, array $payload, string $eventClass): void
    {
        $log = $this->createLog($action, $eventClass, $payload);

        try {
            $handler = $this->resolveHandler($action->action_type);

            $response = $handler->handle($action, $payload, $eventClass);

            $log->update([
                'status' => 'success',
                'response' => $response,
                'executed_at' => now(),
            ]);
        } catch (Throwable $exception) {
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

        if (! $class) {
            throw new InvalidArgumentException(sprintf(
                'No Signal handler registered for action type [%s].',
                $type
            ));
        }

        $handler = App::make($class);

        if (! $handler instanceof SignalActionHandler) {
            throw new InvalidArgumentException(sprintf(
                'Handler [%s] must implement %s.',
                $class,
                SignalActionHandler::class
            ));
        }

        return $handler;
    }
}
