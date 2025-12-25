<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('api');
        RateLimiter::clear('api-auth');
    }

    public function test_token_is_issued_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret-password'),
        ]);

        $response = $this->postJson('/api/auth/token', [
            'login' => $user->login,
            'password' => 'secret-password',
            'device_name' => 'ci-suite',
            'abilities' => ['read'],
        ]);

        $response->assertCreated()->assertJsonStructure([
            'token',
            'token_type',
            'expires_at',
        ]);

        $this->assertDatabaseHas('api_tokens', [
            'user_id' => $user->id,
            'name' => 'ci-suite',
        ]);
    }

    public function test_protected_route_requires_valid_token(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret-password'),
        ]);

        $tokenResponse = $this->postJson('/api/auth/token', [
            'login' => $user->login,
            'password' => 'secret-password',
        ])->assertCreated();

        $token = $tokenResponse->json('token');

        $this->getJson('/api/auth/me')->assertUnauthorized();

        $this->getJson('/api/auth/me', [
            'Authorization' => "Bearer {$token}",
        ])->assertOk()->assertJsonFragment([
            'login' => $user->login,
            'email' => $user->email,
        ]);
    }

    public function test_token_can_be_revoked(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret-password'),
        ]);

        $token = $this->postJson('/api/auth/token', [
            'login' => $user->login,
            'password' => 'secret-password',
        ])->json('token');

        $this->assertDatabaseCount('api_tokens', 1);

        $this->postJson('/api/auth/logout', [], [
            'Authorization' => "Bearer {$token}",
        ])->assertOk();

        $this->assertDatabaseCount('api_tokens', 0);

        $this->getJson('/api/auth/me', [
            'Authorization' => "Bearer {$token}",
        ])->assertUnauthorized();
    }

    public function test_auth_endpoint_respects_rate_limit_configuration(): void
    {
        Config::set('api.auth_rate_limit', 1);
        RateLimiter::clear('api-auth');

        $user = User::factory()->create([
            'password' => bcrypt('secret-password'),
        ]);

        $this->postJson('/api/auth/token', [
            'login' => $user->login,
            'password' => 'secret-password',
        ])->assertCreated();

        $this->postJson('/api/auth/token', [
            'login' => $user->login,
            'password' => 'secret-password',
        ])->assertStatus(429);
    }
}
