<?php

namespace Voodflow\Voodflow\Support;

class ReverseRelationRegistry
{
    /**
     * @var array<string, array<string, array{
     *     key: string,
     *     source_model: class-string,
     *     relation_name: string,
     *     label: string,
     *     foreign_key: string|null,
     *     relation_type: string|null,
     *     model_fields: array<string, mixed>,
     * }>
     */
    protected array $relations = [];

    /**
     * Registra una relazione inversa che punta al modello target.
     *
     * @param  class-string  $targetModel
     */
    public function register(string $targetModel, array $descriptor): void
    {
        $key = $descriptor['key'];

        $this->relations[$targetModel][$key] = $descriptor;
    }

    /**
     * Restituisce tutte le relazioni inverse per un modello target.
     *
     * @param  class-string|null  $targetModel
     * @return array<int, array>
     */
    public function for(?string $targetModel): array
    {
        if (! $targetModel) {
            return [];
        }

        $this->ensureWarm();

        return array_values($this->relations[$targetModel] ?? []);
    }

    public function find(?string $descriptorKey): ?array
    {
        if (! $descriptorKey) {
            return null;
        }

        $this->ensureWarm();

        foreach ($this->relations as $relations) {
            if (isset($relations[$descriptorKey])) {
                return $relations[$descriptorKey];
            }
        }

        return null;
    }

    protected function ensureWarm(): void
    {
        app(ReverseRelationWarmup::class)->warm();
    }
}
