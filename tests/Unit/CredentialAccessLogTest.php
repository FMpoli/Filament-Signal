<?php

namespace Voodflow\Voodflow\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Voodflow\Voodflow\Models\CredentialAccessLog;
use Voodflow\Voodflow\Tests\TestCase;

class CredentialAccessLogTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_log_access_and_detect_suspicious_activity()
    {
        $log = CredentialAccessLog::logAccess(
            credentialId: 1,
            action: 'test_action',
            params: ['foo' => 'bar']
        );

        $this->assertDatabaseHas('voodflow_credential_access_logs', [
            'credential_id' => 1,
            'action' => 'test_action',
        ]);

        $log->markAsSuspicious('Too many requests from this node');
        
        $this->assertTrue($log->refresh()->is_suspicious);
        $this->assertEquals('Too many requests from this node', $log->suspicious_reason);
    }

    /** @test */
    public function it_filters_recent_logs()
    {
        CredentialAccessLog::factory()->count(3)->create([
            'credential_id' => 1,
            'created_at' => now()->subDays(2),
        ]);

        CredentialAccessLog::factory()->count(2)->create([
            'credential_id' => 1,
            'created_at' => now(),
        ]);

        $this->assertCount(2, CredentialAccessLog::recent(24)->get());
    }
}
