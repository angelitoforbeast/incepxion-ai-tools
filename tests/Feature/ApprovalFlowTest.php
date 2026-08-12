<?php

namespace Tests\Feature;

use App\Livewire\AccountRejected;
use App\Livewire\Admin\UserManager;
use App\Livewire\ApprovalPending;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Plan::create(['name' => 'Free', 'slug' => 'free', 'daily_quota' => 20, 'monthly_quota' => 300]);
    }

    public function test_rejected_user_is_sent_to_rejected_page(): void
    {
        $user = User::factory()->create(['status' => 'rejected', 'remarks' => 'spam', 'email_verified_at' => now()]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('account.rejected'));
    }

    public function test_suspended_user_is_sent_to_rejected_page(): void
    {
        $user = User::factory()->create(['status' => 'suspended', 'email_verified_at' => now()]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('account.rejected'));
    }

    public function test_rejected_page_shows_the_reason(): void
    {
        $user = User::factory()->create(['status' => 'rejected', 'remarks' => 'Incomplete profile', 'email_verified_at' => now()]);

        $this->actingAs($user)->get(route('account.rejected'))
            ->assertOk()
            ->assertSee('Account Not Approved')
            ->assertSee('Incomplete profile');
    }

    public function test_pending_poll_advances_to_dashboard_once_approved(): void
    {
        $user = User::factory()->create(['status' => 'pending', 'email_verified_at' => now()]);

        $component = Livewire::actingAs($user)->test(ApprovalPending::class);

        // Admin approves in the background.
        $user->update(['status' => 'approved', 'access_expires_at' => now()->addMonth()]);

        $component->call('refreshStatus')->assertRedirect(route('dashboard'));
    }

    public function test_pending_poll_advances_to_rejected_page_when_rejected(): void
    {
        $user = User::factory()->create(['status' => 'pending', 'email_verified_at' => now()]);

        $component = Livewire::actingAs($user)->test(ApprovalPending::class);

        $user->update(['status' => 'rejected', 'remarks' => 'nope']);

        $component->call('refreshStatus')->assertRedirect(route('account.rejected'));
    }

    public function test_rejected_poll_advances_to_dashboard_when_reapproved(): void
    {
        $user = User::factory()->create(['status' => 'rejected', 'email_verified_at' => now()]);

        $component = Livewire::actingAs($user)->test(AccountRejected::class);

        $user->update(['status' => 'approved', 'access_expires_at' => now()->addMonth()]);

        $component->call('refreshStatus')->assertRedirect(route('dashboard'));
    }

    public function test_approve_fires_a_toast_notification(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $pending = User::factory()->create(['status' => 'pending', 'email_verified_at' => now()]);

        Livewire::actingAs($admin)->test(UserManager::class)
            ->call('approve', $pending->id)
            ->assertDispatched('notify');
    }

    public function test_reject_fires_a_toast_notification(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $pending = User::factory()->create(['status' => 'pending', 'email_verified_at' => now()]);

        Livewire::actingAs($admin)->test(UserManager::class)
            ->call('startReject', $pending->id)
            ->set('rejectRemarks', 'Incomplete')
            ->call('confirmReject')
            ->assertDispatched('notify');

        $this->assertSame('rejected', $pending->fresh()->status);
    }
}
