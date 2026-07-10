<?php

namespace Tests\Feature;

use App\Livewire\Admin\PromptManager;
use App\Livewire\Admin\UserManager;
use App\Models\Plan;
use App\Models\Tool;
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
        Tool::create(['slug' => 'ad-copy-generator', 'name' => 'AI Ad Copy Generator', 'is_active' => true, 'config' => ['default_model' => 'gpt-4o']]);
    }

    public function test_non_admin_cannot_access_admin(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'role' => 'user', 'email_verified_at' => now()]);

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->get('/admin/prompts')->assertForbidden();
    }

    public function test_admin_can_access_admin(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);

        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertSee('User Management');
        $this->actingAs($admin)->get('/admin/prompts')->assertOk()->assertSee('System Prompt');
    }

    public function test_admin_can_preapprove_a_user_by_email(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);

        Livewire::actingAs($admin)->test(UserManager::class)
            ->set('inviteEmail', 'newbie@gmail.com')
            ->set('inviteAdmin', true)
            ->call('invite');

        $this->assertDatabaseHas('users', [
            'email'  => 'newbie@gmail.com',
            'status' => 'approved',
            'role'   => 'admin',
        ]);
    }

    public function test_saving_prompt_creates_a_version(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);

        Livewire::actingAs($admin)->test(PromptManager::class)
            ->set('systemPrompt', 'Version one prompt with {language} rules.')
            ->set('model', 'gpt-4o')
            ->call('save');

        $this->assertDatabaseHas('prompt_versions', ['system_prompt' => 'Version one prompt with {language} rules.']);
    }

    public function test_admin_can_edit_the_ad_copy_prompt(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);

        Livewire::actingAs($admin)->test(PromptManager::class)
            ->set('systemPrompt', 'Custom instructions for {language} with clear rules.')
            ->set('model', 'gpt-4o-mini')
            ->call('save');

        $config = Tool::where('slug', 'ad-copy-generator')->first()->config;
        $this->assertSame('Custom instructions for {language} with clear rules.', $config['system_prompt']);
        $this->assertSame('gpt-4o-mini', $config['default_model']);
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

    public function test_admin_can_reject_a_user_with_remarks(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $pending = User::factory()->create(['status' => 'pending', 'email_verified_at' => now()]);

        Livewire::actingAs($admin)->test(UserManager::class)
            ->call('startReject', $pending->id)
            ->set('rejectRemarks', 'Incomplete profile')
            ->call('confirmReject');

        $fresh = $pending->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame('Incomplete profile', $fresh->remarks);
    }

    public function test_reject_requires_remarks(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $pending = User::factory()->create(['status' => 'pending', 'email_verified_at' => now()]);

        Livewire::actingAs($admin)->test(UserManager::class)
            ->call('startReject', $pending->id)
            ->set('rejectRemarks', '')
            ->call('confirmReject')
            ->assertHasErrors('rejectRemarks');

        $this->assertSame('pending', $pending->fresh()->status);
    }

    public function test_approving_a_rejected_user_clears_remarks(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $rejected = User::factory()->create(['status' => 'rejected', 'remarks' => 'spam', 'email_verified_at' => now()]);

        Livewire::actingAs($admin)->test(UserManager::class)->call('approve', $rejected->id);

        $fresh = $rejected->fresh();
        $this->assertSame('approved', $fresh->status);
        $this->assertNull($fresh->remarks);
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
