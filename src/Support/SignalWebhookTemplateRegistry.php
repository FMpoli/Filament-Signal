<?php

namespace Base33\FilamentSignal\Support;

class SignalWebhookTemplateRegistry
{
    /**
     * @var array<string, SignalWebhookTemplate>
     */
    protected array $templates = [];

    public function register(SignalWebhookTemplate $template): void
    {
        $this->templates[$template->id] = $template;
    }

    /**
     * @return array<string, SignalWebhookTemplate>
     */
    public function all(): array
    {
        return $this->templates;
    }

    public function find(?string $id): ?SignalWebhookTemplate
    {
        if (! $id) {
            return null;
        }

        return $this->templates[$id] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function options(): array
    {
        $options = [];

        foreach ($this->templates as $template) {
            $options[$template->id] = $template->name;
        }

        return $options;
    }
}


