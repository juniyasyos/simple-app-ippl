<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;

class ApiTokenPermissionsTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_api_token_permissions_can_be_updated(): void
    {
        if (!Features::hasApiFeatures()) {
            $this->markTestSkipped('API support is not enabled.');
        }

        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        $token = $user->tokens()->create([
            'name' => 'Test Token',
            'token' => Str::random(40),
            'abilities' => ['create', 'read', 'update'],
        ]);


        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->putJson('/user/api-tokens/' . $token->id, [
                    'name' => 'Updated Token',
                    'permissions' => ['delete', 'missing-permission'],
                ]);

        $response->assertStatus(303);
        $this->assertFalse($user->fresh()->tokens->first()->can('update'));
        $this->assertTrue($user->fresh()->tokens->first()->can('delete'));
        $this->assertFalse($user->fresh()->tokens->first()->can('read'));
        $this->assertFalse($user->fresh()->tokens->first()->can('missing-permission'));

    }
}
