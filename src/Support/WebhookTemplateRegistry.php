<?php

namespace Voodflow\Voodflow\Support;

class WebhookTemplateRegistry
{
    /**
     * @var array<string, WebhookTemplate>
     */
    protected array $templates = [];

    public function register(WebhookTemplate $template): void
    {
        $this->templates[$template->id] = $template;
    }

    /**
     * @return array<string, WebhookTemplate>
     */
    public function all(): array
    {
        return $this->templates;
    }

    public function find(?string $id): ?WebhookTemplate
    {
        if (!$id) {
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
