<?php

namespace Voodflow\Voodflow\Actions;

use Voodflow\Voodflow\Contracts\ActionHandler;
use Voodflow\Voodflow\Models\SignalAction;
use Voodflow\Voodflow\Models\SignalActionLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;

class EmailActionHandler implements ActionHandler
{
    public function handle(SignalAction $action, array $payload, string $eventClass, ?SignalActionLog $log = null): ?array
    {
        $template = $action->template;

        if (! $template) {
            throw new InvalidArgumentException("Signal action [{$action->id}] does not have a template associated.");
        }

        $configuration = $action->configuration ?? [];
        $subject = Arr::get($configuration, 'subject_override', $template->subject ?: Str::headline($action->name));
        $recipients = Arr::get($configuration, 'recipients', []);

        if (blank($recipients)) {
            throw new InvalidArgumentException("Signal action [{$action->id}] is missing recipients configuration.");
        }

        $data = $payload + [
            'eventClass' => $eventClass,
            'trigger' => $action->trigger->toArray(),
            'action' => $action->toArray(),
        ];

        $html = Blade::render($template->content_html, $data);
        $text = $template->content_text ? Blade::render($template->content_text, $data) : strip_tags($html);

        Mail::send([], [], function ($message) use ($subject, $recipients, $html, $text): void {
            $message->subject($subject);

            foreach ((array) Arr::get($recipients, 'to', []) as $address => $name) {
                is_int($address)
                    ? $message->to($name)
                    : $message->to($address, $name);
            }

            foreach ((array) Arr::get($recipients, 'cc', []) as $address => $name) {
                is_int($address)
                    ? $message->cc($name)
                    : $message->cc($address, $name);
            }

            foreach ((array) Arr::get($recipients, 'bcc', []) as $address => $name) {
                is_int($address)
                    ? $message->bcc($name)
                    : $message->bcc($address, $name);
            }

            $message->setBody($html, 'text/html');

            if (! blank($text)) {
                $message->addPart($text, 'text/plain');
            }
        });

        return [
            'subject' => $subject,
            'recipients' => $recipients,
        ];
    }
}
