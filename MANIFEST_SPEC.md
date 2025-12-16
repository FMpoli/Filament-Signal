# Node Manifest Specification

## manifest.json

Every Voodflow node package must include a `manifest.json` file with the following structure:

```json
{
  "name": "email-node",
  "display_name": "Email Sender",
  "version": "1.0.0",
  "author": "Acme Corp",
  "author_url": "https://acme.com",
  "description": "Send emails via SMTP with advanced templating",
  "icon": "heroicon-o-envelope",
  "color": "blue",
  "category": "action",
  "tier": "PRO",
  "license": {
    "type": "commercial",
    "requires_activation": true,
    "anystack_product_id": "prod_abc123",
    "validation_url": "https://api.anystack.sh/v1/licenses/validate"
  },
  "voodflow": {
    "min_version": "1.0.0",
    "max_version": "2.0.0"
  },
  "php": {
    "class": "EmailNode",
    "namespace": "Acme\\VoodflowNodes\\EmailNode"
  },
  "javascript": {
    "component": "EmailNode",
    "bundle": "dist/email-node.js"
  },
  "dependencies": {
    "php": {
      "guzzlehttp/guzzle": "^7.0"
    },
    "npm": {
      "react": "^18.0.0"
    }
  },
  "config_schema": {
    "license_key": {
      "type": "string",
      "label": "License Key",
      "required": true,
      "encrypted": true,
      "description": "Enter your Anystack license key"
    },
    "smtp_host": {
      "type": "string",
      "label": "SMTP Host",
      "default": "smtp.gmail.com"
    },
    "smtp_port": {
      "type": "number",
      "label": "SMTP Port",
      "default": 587
    }
  }
}
```

## Tier Types

- `FREE` - Free to use
- `CORE` - Included in Voodflow core
- `PRO` - Requires Voodflow Pro license
- `PREMIUM` - Premium paid node (individual purchase)

## License Types

- `open-source` - MIT, GPL, etc.
- `commercial` - Requires purchase/activation
- `subscription` - Recurring payment required

## Package Structure

```
email-node/
├── manifest.json              # Required
├── README.md                  # Optional but recommended
├── src/
│   ├── EmailNode.php         # Backend node class
│   └── components/
│       └── EmailNode.jsx     # React component
├── dist/
│   └── email-node.js         # Pre-compiled bundle
└── composer.json             # For Composer distribution
```

## Validation Rules

1. `name` must be unique and kebab-case
2. `version` must follow semver
3. `tier` must be one of: FREE, CORE, PRO, PREMIUM
4. If `tier` is PRO or PREMIUM, `license` section is required
5. `php.class` must match the actual PHP class name
6. `javascript.bundle` must exist in the package
