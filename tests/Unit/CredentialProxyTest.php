<?php

namespace Voodflow\Voodflow\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Voodflow\Voodflow\Exceptions\UnauthorizedCredentialAccessException;
use Voodflow\Voodflow\Models\Credential;
use Voodflow\Voodflow\Services\CredentialProxy;
use Voodflow\Voodflow\Tests\TestCase;

class CredentialProxyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_provides_secure_access_to_credentials()
    {
        $credential = Credential::create([
            'user_id' => 1,
            'name' => 'My API',
            'type' => 'api_token',
            'credentials' => ['api_key' => 'secret-key-123'],
        ]);

        $proxy = new CredentialProxy($credential, nodeId: 10, workflowId: 5);
        $data = $proxy->execute('get_api_client');

        $this->assertEquals('secret-key-123', $data['api_key']);

        // Verify audit log was created
        $this->assertDatabaseHas('voodflow_credential_access_logs', [
            'credential_id' => $credential->id,
            'node_id' => 10,
            'action' => 'get_api_client',
            'status' => 'success',
        ]);
    }

    /** @test */
    public function it_enforces_scopes()
    {
        $this->expectException(UnauthorizedCredentialAccessException::class);

        $credential = Credential::create([
            'user_id' => 1,
            'name' => 'Secure SMTP',
            'type' => 'basic_auth',
            'credentials' => ['host' => 'localhost'],
        ]);

        // Node only has 'other.scope', but tries to access SMTP
        $proxy = new CredentialProxy(
            $credential,
            nodeId: 10,
            allowedScopes: ['other.scope']
        );

        $proxy->execute('get_smtp_client');
    }

    /** @test */
    public function it_sanitizes_logs()
    {
        $credential = Credential::create([
            'user_id' => 1,
            'name' => 'Sensitive API',
            'type' => 'api_token',
            'credentials' => ['api_key' => 'VERY-SENSITIVE'],
        ]);

        $proxy = new CredentialProxy($credential, nodeId: 10);

        // Passing sensitive data in params
        $proxy->execute('get_api_client', ['password' => 'my-plain-password']);

        $log = \Voodflow\Voodflow\Models\CredentialAccessLog::first();
        $this->assertEquals('[REDACTED]', $log->params['password']);
    }
}
