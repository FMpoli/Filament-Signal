# Using Credentials in Your Nodes

This guide shows how to develop a node that securely uses the Voodflow credentials system.

## 1. Declare required scopes in `manifest.json`

Your node must declare which credential scopes it needs.

```json
{
  "name": "Send Message",
  "type": "action",
  "credential_scopes": [
    "api.request"
  ]
}
```

## 2. Request the credential in `execute()`

You can access credentials via the `ExecutionContext`. The system will automatically return a `CredentialProxy`.

```php
public function execute(ExecutionContext $context): ExecutionResult
{
    // 1. Get the credential ID from the node configuration
    $credentialId = $context->getConfig('credential_id');
    
    if (!$credentialId) {
        return ExecutionResult::failure("No credential selected");
    }

    try {
        // 2. Request the secure proxy (passing the manifest for scope verification)
        $proxy = $context->getCredential($credentialId, $this->manifest);

        // 3. Execute an action to get the client configuration
        // This will verify that the node has the 'api.request' scope
        $apiConfig = $proxy->execute('get_api_client');

        // 4. Use the configuration to make your request
        $response = Http::withToken($apiConfig['api_key'])
            ->post($apiConfig['base_url'] . '/messages', [
                'body' => $context->getInput('message')
            ]);

        return ExecutionResult::success($response->json());

    } catch (UnauthorizedCredentialAccessException $e) {
        return ExecutionResult::failure("Security Error: " . $e->getMessage());
    } catch (\Exception $e) {
        return ExecutionResult::failure($e->getMessage());
    }
}
```

## Available Proxy Actions

The `CredentialProxy` supports several standard actions based on the credential type:

| Action | Credential Type | Returned Scopes |
|--------|-----------------|-----------------|
| `get_smtp_client` | `basic_auth` | `host`, `port`, `username`, `password` |
| `get_oauth_client` | `oauth2` | `client_id`, `client_secret`, `access_token` |
| `get_api_client` | `api_token` | `api_key`, `api_secret`, `base_url` |
| `get_ssh_connection` | `ssh_key` | `private_key`, `host`, `port`, `username` |

## Security Best Practices

1.  **Never Use Raw Model**: Always use `$context->getCredential()` which returns a proxy.
2.  **Request Min Scopes**: Only ask for the scopes your node actually needs.
3.  **Handle Exceptions**: Always wrap credential access in a try/catch block to handle `UnauthorizedCredentialAccessException`.
