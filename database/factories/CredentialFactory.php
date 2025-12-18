<?php

namespace Voodflow\Voodflow\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Voodflow\Voodflow\Models\Credential;

class CredentialFactory extends Factory
{
    protected $model = Credential::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'name' => $this->faker->word . ' API',
            'type' => 'api_token',
            'provider' => $this->faker->randomElement(['google', 'stripe', 'openai']),
            'credentials' => ['api_key' => 'sk_' . $this->faker->md5],
            'status' => 'active',
        ];
    }
}
