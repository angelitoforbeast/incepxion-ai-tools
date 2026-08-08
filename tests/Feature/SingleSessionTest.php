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
}
