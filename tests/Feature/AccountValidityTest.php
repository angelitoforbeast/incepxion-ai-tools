<?php

namespace Tests\Feature;

use App\Livewire\Admin\BillingSettings;
use App\Livewire\Admin\SubscriptionLogs;
use App\Livewire\Admin\SubscriptionManager;
use App\Livewire\Admin\UserManager;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccountValidityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Plan::create(['name' => 'Free', 'slug' => 'free', 'daily_quota' => 20, 'monthly_quota' => 300]);
        Tool::create(['slug' => 'ad-copy-generator', 'name' => 'AI Ad Copy Generator', 'is_active' => true, 'config' => ['default_model' => 'gpt-4o']]);
    }

    public function test_expired_user_is_redirected_to_settle(): void
    {
        $user = User::factory()->create([
            'status'            => 'approved',
            'email_verified_at' => now(),
            'access_expires_at' => now()->subDay(),
        ]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('settle'));
        $this->actingAs($user)->get('/tools/ad-copy-generator')->assertRedirect(route('settle'));
    }

    public function test_non_expired_user_reaches_dashboard(): void
    {
        $user = User::factory()->create([
            'status'            => 'approved',
            'email_verified_at' => now(),
            'access_expires_at' => now()->addMonth(),
        ]);

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_user_with_no_expiry_is_not_blocked(): void
    {
        $user = User::factory()->create([
            'status'            => 'approved',
            'email_verified_at' => now(),
            'access_expires_at' => null,
        ]);

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_expired_user_can_still_reach_settle_page(): void
    {
        $user = User::factory()->create([
            'status'            => 'approved',
            'email_verified_at' => now(),
            'access_expires_at' => now()->subDay(),
        ]);

        $this->actingAs($user)->get(route('settle'))->assertOk()->assertSee('Settle your account');
    }

    public function test_expired_admin_is_not_blocked(): void
    {
        $admin = User::factory()->create([
            'status'            => 'approved',
            'role'              => 'admin',
            'email_verified_at' => now(),
            'access_expires_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)->get('/dashboard')->assertOk();
    }

    public function test_approve_sets_default_three_month_validity(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $pending = User::factory()->create(['status' => 'pending', 'email_verified_at' => now(), 'access_expires_at' => null]);

        Livewire::actingAs($admin)->test(UserManager::class)->call('approve', $pending->id);

        $exp = $pending->fresh()->access_expires_at;
        $this->assertNotNull($exp);
        // ~3 months out (allow a day of slack).
        $this->assertTrue($exp->between(now()->addMonths(3)->subDay(), now()->addMonths(3)->addDay()));
    }

    public function test_extend_access_bumps_expiry_from_future_date(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $base = now()->addMonth()->startOfDay();
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now(), 'access_expires_at' => $base]);

        Livewire::actingAs($admin)->test(SubscriptionManager::class)
            ->call('manage', $user->id)
            ->call('extend', 3);

        $this->assertEquals($base->copy()->addMonths(3)->toDateString(), $user->fresh()->access_expires_at->toDateString());
    }

    public function test_extend_access_on_lapsed_account_counts_from_now(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now(), 'access_expires_at' => now()->subMonths(2)]);

        Livewire::actingAs($admin)->test(SubscriptionManager::class)
            ->call('manage', $user->id)
            ->call('extend', 1);

        $exp = $user->fresh()->access_expires_at;
        $this->assertTrue($exp->isFuture());
        $this->assertEquals(now()->addMonth()->toDateString(), $exp->toDateString());
    }

    public function test_extend_access_rejects_invalid_month_values(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now(), 'access_expires_at' => now()->addMonth()]);
        $before = $user->access_expires_at->toDateTimeString();

        Livewire::actingAs($admin)->test(SubscriptionManager::class)
            ->call('manage', $user->id)
            ->call('extend', 7);

        $this->assertEquals($before, $user->fresh()->access_expires_at->toDateTimeString());
    }

    public function test_admin_can_set_exact_expiry_date(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now(), 'access_expires_at' => now()->addMonth()]);
        $target = now()->addMonths(5)->toDateString();

        Livewire::actingAs($admin)->test(SubscriptionManager::class)
            ->call('manage', $user->id)
            ->set('newDate', $target)
            ->call('setDate')
            ->assertHasNoErrors();

        $this->assertEquals($target, $user->fresh()->access_expires_at->toDateString());
    }

    public function test_setting_a_past_date_expires_the_account(): void
    {
        // Past dates are allowed so an admin can force-expire an account for testing.
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now(), 'access_expires_at' => now()->addMonth()]);

        Livewire::actingAs($admin)->test(SubscriptionManager::class)
            ->call('manage', $user->id)
            ->set('newDate', now()->subDay()->toDateString())
            ->call('setDate')
            ->assertHasNoErrors();

        $this->assertTrue($user->fresh()->isExpired());
    }

    public function test_extend_writes_a_subscription_log_with_note(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now(), 'access_expires_at' => now()->addMonth()]);

        Livewire::actingAs($admin)->test(SubscriptionManager::class)
            ->call('manage', $user->id)
            ->set('note', 'Paid 500 via GCash')
            ->call('extend', 3);

        $this->assertDatabaseHas('subscription_logs', [
            'user_id'  => $user->id,
            'admin_id' => $admin->id,
            'action'   => 'extend',
            'months'   => 3,
            'note'     => 'Paid 500 via GCash',
        ]);
    }

    public function test_approve_writes_a_subscription_log(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $pending = User::factory()->create(['status' => 'pending', 'email_verified_at' => now(), 'access_expires_at' => null]);

        Livewire::actingAs($admin)->test(UserManager::class)->call('approve', $pending->id);

        $this->assertDatabaseHas('subscription_logs', [
            'user_id' => $pending->id,
            'action'  => 'approve',
        ]);
    }

    public function test_extend_access_method_removed_from_user_manager(): void
    {
        $this->assertFalse(method_exists(UserManager::class, 'extendAccess'));
    }

    public function test_admin_can_view_subscription_log_page(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $user = User::factory()->create(['status' => 'approved', 'name' => 'Loggy McUser', 'email_verified_at' => now(), 'access_expires_at' => now()->addMonth()]);

        Livewire::actingAs($admin)->test(SubscriptionManager::class)
            ->call('manage', $user->id)
            ->call('extend', 3);

        $this->actingAs($admin)->get(route('admin.subscriptions.log'))
            ->assertOk()
            ->assertSee('Subscription change log')
            ->assertSee('Loggy McUser');
    }

    public function test_subscription_log_filters_by_action(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $extended = User::factory()->create(['status' => 'approved', 'name' => 'Extended User', 'email_verified_at' => now(), 'access_expires_at' => now()->addMonth()]);
        $pending = User::factory()->create(['status' => 'pending', 'name' => 'Approved User', 'email_verified_at' => now()]);

        Livewire::actingAs($admin)->test(SubscriptionManager::class)->call('manage', $extended->id)->call('extend', 3);
        Livewire::actingAs($admin)->test(UserManager::class)->call('approve', $pending->id);

        Livewire::actingAs($admin)->test(SubscriptionLogs::class)
            ->set('action', 'extend')
            ->assertViewHas('rows', fn ($rows) => $rows->count() === 1
                && $rows->first()->action === 'extend'
                && $rows->first()->user->name === 'Extended User');
    }

    public function test_admin_can_save_settle_billing_details(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);

        Livewire::actingAs($admin)->test(BillingSettings::class)
            ->set('message', 'Please renew to continue.')
            ->set('gcash', '0917 000 1111')
            ->set('bank', 'BPI 1234')
            ->set('contact', 'm.me/incepxion')
            ->call('save');

        $settle = Setting::get('settle');
        $this->assertSame('Please renew to continue.', $settle['message']);
        $this->assertSame('0917 000 1111', $settle['gcash']);
    }
}
