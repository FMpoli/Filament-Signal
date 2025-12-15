# Voodflow

**An n8n-like visual workflow automation system for FilamentPHP**

Voodflow is a powerful automation plugin for Filament 4 that enables you to create visual workflows triggered by any Laravel event. Build complex automation flows with a drag-and-drop interface, manage templates, send webhooks, and track execution logs—all from within your Filament admin panel.

## Features

- 🔄 **Visual Flow Editor**: Intuitive React Flow-based editor for building workflows
- ⚡ **Event-Driven**: Listen to any Laravel event and trigger automated responses
- 🎯 **Flexible Actions**: Log, webhook, email, and custom action handlers
- 📊 **Execution Tracking**: Complete audit trail of all workflow executions
- 🎨 **Template Management**: Built-in Tiptap editor for email and message templates
- 🔌 **Extensible**: Easy plugin system for custom nodes and action handlers
- 🌐 **Model Integration**: Auto-discover and integrate with your Eloquent models
- 📝 **Payload Configuration**: Fine-tune what data gets passed through your workflows

## Installation

Install the package via composer:

```bash
composer require voodflow/voodflow
```

Run the migrations:

```bash
php artisan migrate
```

Publish the configuration file (optional):

```bash
php artisan vendor:publish --tag="voodflow-config"
```

Publish the assets (optional):

```bash
php artisan vendor:publish --tag="voodflow-assets"
```

## Configuration

The published configuration file (`config/voodflow.php`) allows you to customize:

- Table names
- Model classes
- Action handlers
- Webhook templates
- Event discovery settings

## Usage

### Creating a Workflow

1. Navigate to the **Workflows** section in your Filament panel
2. Click **New Workflow**
3. Use the visual editor to:
   - Add a **Trigger Node** (select the Laravel event to listen for)
   - Add **Filter Nodes** to conditionally process data
   - Add **Action Nodes** to perform operations (webhook, email, log, etc.)
4. Connect the nodes by dragging between their handles
5. Configure each node's settings
6. Save and activate your workflow

### Available Node Types

#### Trigger Nodes
- **Event Trigger**: Listens to Laravel events
- Accesses event data and model relationships
- Configurable payload fields

#### Filter Nodes
- **Basic Filter**: Simple conditional logic
- **Advanced Filter**: Complex multi-condition filtering
- Access to event payload data

#### Action Nodes
- **Log**: Record execution data
- **Webhook**: Send HTTP requests with custom payloads
- **Email**: Send templated emails (coming soon)
- **Custom**: Build your own action handlers

### Registering Events from Your Plugins

Plugins can expose their events to make them discoverable in the trigger selector:

```php
use Voodflow\Voodflow\Voodflow;

public function boot(): void
{
    Voodflow::registerEvent(
        eventClass: \App\Events\OrderCreated::class,
        name: 'Order Created',
        description: 'Triggered when a new order is created',
        group: 'E-commerce'
    );
}
```

### Model Integration

Voodflow can automatically discover your Eloquent models and their relationships:

1. Go to **Model Integrations** in your Filament panel
2. Select a model to integrate
3. Choose which events to track (created, updated, deleted, etc.)
4. The model's fields and relationships become available in your workflows

## Building Custom Nodes

Generate a new node using the artisan command:

```bash
php artisan signal:make-node
```

This will guide you through creating:
- A React component for the visual node
- A PHP handler class for backend logic
- Proper registration in the node registry

### Example: Custom Action Handler

```php
<?php

namespace App\Voodflow\Actions;

use Voodflow\Voodflow\Contracts\ActionHandler;
use Voodflow\Voodflow\Models\Action;
use Voodflow\Voodflow\Models\ActionLog;

class TelegramActionHandler implements ActionHandler
{
    public function handle(
        Action $action, 
        array $payload, 
        string $eventClass, 
        ?ActionLog $log = null
    ): ?array {
        $config = $action->configuration ?? [];
        $botToken = $config['bot_token'] ?? null;
        $chatId = $config['chat_id'] ?? null;
        $message = $config['message'] ?? 'Event triggered';
        
        // Send message to Telegram
        $response = Http::post(
            "https://api.telegram.org/bot{$botToken}/sendMessage",
            [
                'chat_id' => $chatId,
                'text' => $this->formatMessage($message, $payload),
            ]
        );
        
        return [
            'sent' => true,
            'message_id' => $response->json('result.message_id'),
        ];
    }
    
    protected function formatMessage(string $template, array $payload): string
    {
        // Replace placeholders with actual values
        foreach ($payload as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }
        
        return $template;
    }
}
```

Register your custom handler in `config/voodflow.php`:

```php
'action_handlers' => [
    'log' => \Voodflow\Voodflow\Actions\LogActionHandler::class,
    'webhook' => \Voodflow\Voodflow\Actions\WebhookActionHandler::class,
    'telegram' => \App\Voodflow\Actions\TelegramActionHandler::class,
],
```

## Frontend Development

The visual flow editor is built with React and requires compilation:

```bash
cd packages/Voodflow/Voodflow
npm install
npm run build
```

This will:
1. Compile the React components
2. Process Tailwind CSS
3. Copy assets to the public directory

### Development Mode

For active development with hot reloading:

```bash
npm run dev
```

## Architecture

### Flow Execution

1. **Event Detection**: Laravel fires an event
2. **Trigger Matching**: Voodflow finds workflows listening to that event
3. **Flow Processing**: Executes nodes in order, respecting connections
4. **Action Execution**: Runs configured actions with the event payload
5. **Logging**: Records execution details and results

### Payload System

Workflows operate on "payloads" - structured data passed between nodes:

- **Event Payload**: Initial data from the Laravel event
- **Filter Payload**: Conditionally modified data
- **Action Payload**: Final data sent to action handlers

Configure which fields to include using the built-in payload field analyzer.

## Testing

Run the test suite:

```bash
composer test
```

## Troubleshooting

### Workflows Not Triggering

1. Ensure the workflow is **activated**
2. Check that the event class name matches exactly
3. Verify event filters are configured correctly
4. Check execution logs for error messages

### Visual Editor Issues

1. Clear browser cache
2. Rebuild frontend assets: `npm run build`
3. Check browser console for JavaScript errors
4. Ensure Filament assets are published

### Performance

For high-volume events:
- Use queue-based processing
- Add appropriate database indexes
- Consider caching frequently-accessed data

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security

Please review [our security policy](../../security/policy) for reporting vulnerabilities.

## Credits

- [Francesco Mulassano](https://github.com/FMPoli)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
