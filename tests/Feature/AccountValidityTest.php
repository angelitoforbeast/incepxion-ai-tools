<?php

namespace Tests\Feature;

use App\Livewire\Admin\BillingSettings;
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

        Livewire::actingAs($admin)->test(UserManager::class)->call('extendAccess', $user->id, 3);

        $this->assertEquals($base->copy()->addMonths(3)->toDateString(), $user->fresh()->access_expires_at->toDateString());
    }

    public function test_extend_access_on_lapsed_account_counts_from_now(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now(), 'access_expires_at' => now()->subMonths(2)]);

        Livewire::actingAs($admin)->test(UserManager::class)->call('extendAccess', $user->id, 1);

        $exp = $user->fresh()->access_expires_at;
        $this->assertTrue($exp->isFuture());
        $this->assertEquals(now()->addMonth()->toDateString(), $exp->toDateString());
    }

    public function test_extend_access_rejects_invalid_month_values(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now(), 'access_expires_at' => now()->addMonth()]);
        $before = $user->access_expires_at->toDateTimeString();

        Livewire::actingAs($admin)->test(UserManager::class)->call('extendAccess', $user->id, 7);

        $this->assertEquals($before, $user->fresh()->access_expires_at->toDateTimeString());
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
