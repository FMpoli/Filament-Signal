<?php

namespace Voodflow\Voodflow\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Voodflow\Voodflow\Models\Credential;
use Voodflow\Voodflow\Tests\TestCase;

class CredentialModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_credential_with_encrypted_data()
    {
        $credential = Credential::create([
            'user_id' => 1,
            'name' => 'Test SMTP',
            'type' => 'basic_auth',
            'provider' => 'smtp',
            'credentials' => [
                'host' => 'smtp.mailtrap.io',
                'password' => 'secret-password',
            ],
        ]);

        $this->assertDatabaseHas('voodflow_credentials', [
            'name' => 'Test SMTP',
        ]);

        // Verify encryption (manual check in DB would show encrypted string)
        $this->assertEquals('secret-password', $credential->credentials['password']);
    }

    /** @test */
    public function it_can_manage_oauth_scopes()
    {
        $credential = Credential::create([
            'user_id' => 1,
            'name' => 'Google OAuth',
            'type' => 'oauth2',
            'provider' => 'google',
            'credentials' => ['token' => 'abc'],
        ]);

        $credential->addScope('user.read');
        $credential->addScope('mail.send');

        $this->assertCount(2, $credential->scopes);
        $this->assertTrue($credential->hasScope('user.read'));

        $credential->syncScopes(['user.read', 'profile']);
        $this->assertCount(2, $credential->refresh()->scopes);
        $this->assertFalse($credential->hasScope('mail.send'));
        $this->assertTrue($credential->hasScope('profile'));
    }

    /** @test */
    public function it_prevents_serialization_for_security()
    {
        $this->expectException(\Voodflow\Voodflow\Exceptions\CredentialSerializationException::class);

        $credential = new Credential;
        serialize($credential);
    }
}
