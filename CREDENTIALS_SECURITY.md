# Voodflow Credentials Security Architecture

Voodflow is designed with a **Security-First** approach to managing sensitive credentials. This document outlines the security layers implemented to protect your data.

## 1. Data Encryption (At Rest)
All sensitive credential data (passwords, API keys, private keys, OAuth2 tokens) are **automatically encrypted** before being stored in the database.
- We use Laravel's `encrypted` cast on the `Credential` model.
- The encryption uses the `AES-256-CBC` algorithm with your application's `APP_KEY`.
- Even if the database is compromised, the sensitive data remains unreadable without the encryption key.

## 2. The Credential Proxy (In Motion)
Nodes **never receive raw credentials**. Interactions are handled through a `CredentialProxy`:
- **Abstraction**: Nodes request an "action" (e.g., `get_smtp_client`) instead of reading raw fields.
- **Filtering**: The proxy only returns the minimum necessary fields for that specific action.
- **Protection**: The proxy implements `__debugInfo()` and `__sleep()` to prevent accidental leaking of data during logging, dumping (`dd()`), or serialization.

## 3. Scope-Based Authorization
Voodflow implements a mandatory scope system to ensure the **Principle of Least Privilege**:
- **Manifest Declaration**: Node developers must declare exactly which scopes they need in their `manifest.json`.
- **Enforcement**: If a node tries to access a credential without having the required scope, a `UnauthorizedCredentialAccessException` is thrown.
- **User Control**: Users can see exactly which permissions a node is requesting before deploying a workflow.

## 4. Full Audit Trail
Every single access to a credential is logged in the `voodflow_credential_access_logs` table:
- **Sanitization**: All parameters passed to the proxy are automatically sanitized before logging (redacting keys like `password`, `secret`, etc.).
- **Metadata**: We track the Node ID, Workflow ID, Action, Timestamp, IP Address, and User Agent.
- **Suspicious Activity**: The system includes a framework for marking and reporting suspicious access patterns.

## 5. Safe Serialization
To prevent credentials from being accidentally serialized (e.g., when a job is sent to a queue), the `Credential` model and the `CredentialProxy` both throw a `CredentialSerializationException` if serialization is attempted.

## 6. Secure Webhooks
Inbound webhooks are protected by multiple authentication methods:
- **Secret Keys**: Validating a specific header value.
- **HMAC Signatures**: Verifying the payload integrity using a shared secret and a signature header (e.g., `X-Hub-Signature`).
- **Bearer Tokens**: Standard token-based authentication.

## 7. Workflow & Ownership Segregation
- **User Ownership**: Credentials are tied to a specific `user_id`. One user cannot use or even see another user's credentials.
- **Panel Access**: Proper Filament policies ensure that only authorized users can manage credentials.
