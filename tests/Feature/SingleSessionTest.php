<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_mismatch_signs_the_user_out(): void
    {
        // Account is "active" on another device (different session id).
        $user = User::factory()->create([
            'status'             => 'approved',
            'current_session_id' => 'session-on-another-device',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_no_recorded_session_does_not_block(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'current_session_id' => null]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_heartbeat_ping_returns_409_when_session_stale(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'current_session_id' => 'other-device']);

        // AJAX ping (Accept: application/json) → middleware signals the player to stop.
        $this->actingAs($user)
            ->getJson('/session/ping')
            ->assertStatus(409);
    }

    public function test_heartbeat_ping_ok_for_active_session(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'current_session_id' => null]);

        $this->actingAs($user)
            ->getJson('/session/ping')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }
}
