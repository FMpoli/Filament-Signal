<?php

namespace Voodflow\Voodflow\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Voodflow\Voodflow\Models\CredentialAccessLog;

class CredentialAccessLogFactory extends Factory
{
    protected $model = CredentialAccessLog::class;

    public function definition(): array
    {
        return [
            'credential_id' => 1,
            'action' => 'get_api_client',
            'params' => ['foo' => 'bar'],
            'status' => 'success',
            'created_at' => now(),
        ];
    }
}
