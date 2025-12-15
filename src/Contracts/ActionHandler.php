<?php

namespace Voodflow\Voodflow\Contracts;

/**
 * Interface for action handlers
 * Each action type (webhook, email, etc.) should implement this interface
 * 
 * Handlers execute actions within workflow nodes and return results
 * for storage in ExecutionNode
 */
interface ActionHandler
{
    /**
     * Execute the action
     *
     * @param array $config Action configuration from the node
     * @param array $payload Event payload data passed through the workflow  
     * @param string $eventClass The event class that triggered this workflow
     * @return array|null Response data to be stored in ExecutionNode.output
     */
    public function handle(array $config, array $payload, string $eventClass): ?array;
}
