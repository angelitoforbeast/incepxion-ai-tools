<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase1Test extends TestCase
{
    use RefreshDatabase;

    protected Plan $free;

    protected function setUp(): void
    {
        parent::setUp();

        $this->free = Plan::create([
            'name' => 'Free', 'slug' => 'free', 'daily_quota' => 20, 'monthly_quota' => 300,
        ]);
    }

    public function test_guests_are_redirected_from_dashboard_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_new_users_get_free_plan_and_pending_status(): void
    {
        $user = User::create(['name' => 'Juan', 'email' => 'juan@test.com', 'password' => 'secret123']);

        $this->assertSame($this->free->id, $user->plan_id);
        $this->assertSame('pending', $user->status);
    }

    public function test_pending_users_are_sent_to_approval_page(): void
    {
        $user = User::factory()->create(['status' => 'pending', 'email_verified_at' => now()]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('approval.pending'));
    }

    public function test_approved_users_can_access_dashboard(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_profile_page_shows_byok_api_key_form(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('OpenAI API Key');
    }

    public function test_api_key_is_stored_encrypted_not_plaintext(): void
    {
        $user = User::factory()->create();

        $key = new UserApiKey(['user_id' => $user->id, 'provider' => 'openai']);
        $key->setKey('sk-super-secret-1234');
        $key->save();

        $raw = DB::table('user_api_keys')->where('id', $key->id)->value('key_encrypted');

        $this->assertNotSame('sk-super-secret-1234', $raw);
        $this->assertSame('sk-super-secret-1234', $key->fresh()->plainKey());
        $this->assertSame('1234', $key->fresh()->key_last4);
    }
}
