# Creating Custom Signal Nodes

This guide explains how to create custom workflow nodes for the Signal automation system.

## Quick Start

Use the built-in generator command to create a new node skeleton:

```bash
php artisan signal:make-node
```

This will launch an interactive wizard that asks you:
1. **Node name** - e.g., `SendEmail`, `WebhookCall`, `SlackNotification`
2. **Node type** - `trigger`, `filter`, or `action`
3. **Color** - Header color (orange, purple, blue, green, red, gray)
4. **Icon** - Node icon (bolt, filter, bell, mail, code, webhook)
5. **Description** - A short description of what the node does

## Generated Files

The command generates two files:

### 1. React Component (`resources/js/components/{Name}Node.jsx`)

The frontend component that renders the node in the flow editor.

### 2. PHP Handler (`src/Nodes/{Name}Node.php`)

The backend class that handles node execution and validation.

## Node Types

| Type | Color | Handles | Purpose |
|------|-------|---------|---------|
| trigger | Orange | Output only | Start the workflow when an event occurs |
| filter | Purple | Input + Output | Filter data based on conditions |
| action | Blue | Input only | Perform an action (send email, call API, etc.) |

## Available Colors

orange, purple, blue, green, red, gray

## Available Icons

bolt, filter, bell, mail, code, webhook

## Next Steps After Generation

1. Edit the React component to add your custom UI fields
2. Edit the PHP handler to add your backend logic
3. Register the node in `FlowEditor.jsx` nodeTypes
4. Run `npm run build`
