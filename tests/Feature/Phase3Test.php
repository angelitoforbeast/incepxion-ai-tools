<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserManager;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Phase3Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Plan::create(['name' => 'Free', 'slug' => 'free', 'daily_quota' => 20, 'monthly_quota' => 300]);
    }

    public function test_non_admin_cannot_access_admin(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'role' => 'user', 'email_verified_at' => now()]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_access_admin(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('User Management');
    }

    public function test_plain_blade_pages_load_livewire_alpine_scripts(): void
    {
        // The dropup + mobile toggle need Alpine, which ships with Livewire's script.
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('livewire.js', false);

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('livewire.js', false);
    }

    public function test_admin_can_approve_a_pending_user(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $pending = User::factory()->create(['status' => 'pending', 'email_verified_at' => now()]);

        Livewire::actingAs($admin)->test(UserManager::class)
            ->call('approve', $pending->id);

        $this->assertSame('approved', $pending->fresh()->status);
        $this->assertSame($admin->id, $pending->fresh()->approved_by);
    }

    public function test_admin_can_promote_a_user_to_admin(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $user = User::factory()->create(['status' => 'approved', 'role' => 'user', 'email_verified_at' => now()]);

        Livewire::actingAs($admin)->test(UserManager::class)->call('makeAdmin', $user->id);

        $this->assertSame('admin', $user->fresh()->role);
    }

    public function test_admin_cannot_remove_their_own_admin_role(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);

        Livewire::actingAs($admin)->test(UserManager::class)->call('removeAdmin', $admin->id);

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_admin_can_delete_a_user(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);

        Livewire::actingAs($admin)->test(UserManager::class)->call('deleteUser', $user->id);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);

        Livewire::actingAs($admin)->test(UserManager::class)->call('deleteUser', $admin->id);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
