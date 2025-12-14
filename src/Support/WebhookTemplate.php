<?php

namespace Voodflow\Voodflow\Support;

class WebhookTemplate
{
    public function __construct(
        public string $id,
        public string $name,
        public string $eventClass,
        public ?string $description = null,
        public array $defaults = [],
    ) {
    }

    public static function make(string $id, string $name, string $eventClass): self
    {
        return new self($id, $name, $eventClass);
    }

    /**
     * @param  array{id: string, name: string, event_class: string, description?: string, defaults?: array<string, mixed>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['event_class'],
            $data['description'] ?? null,
            $data['defaults'] ?? [],
        );
    }
}
