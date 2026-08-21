<?php

namespace Tests\Feature;

use App\Livewire\Admin\CourseManager;
use App\Models\Plan;
use App\Models\User;
use App\Support\WatermarkCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WatermarkCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Plan::create(['name' => 'Free', 'slug' => 'free', 'daily_quota' => 20, 'monthly_quota' => 300]);
    }

    public function test_code_is_short_and_uses_the_unambiguous_alphabet(): void
    {
        $code = WatermarkCode::for(42);

        $this->assertSame(6, strlen($code));
        // I, L, O and U are excluded so the code survives being read off a screenshot.
        $this->assertDoesNotMatchRegularExpression('/[ILOU]/', $code);
        $this->assertMatchesRegularExpression('/^[0-9A-Z]{6}$/', $code);
    }

    public function test_code_is_stable_for_the_same_user_and_differs_between_users(): void
    {
        $this->assertSame(WatermarkCode::for(42), WatermarkCode::for(42));
        $this->assertNotSame(WatermarkCode::for(42), WatermarkCode::for(43));
    }

    public function test_code_resolves_back_to_the_account(): void
    {
        $user = User::factory()->create();

        $found = WatermarkCode::resolve(WatermarkCode::for($user->id));

        $this->assertNotNull($found);
        $this->assertSame($user->id, $found->id);
    }

    public function test_resolve_accepts_the_prefix_and_lowercase(): void
    {
        $user = User::factory()->create();
        $code = WatermarkCode::for($user->id);

        $this->assertSame($user->id, WatermarkCode::resolve('WM-'.$code)?->id);
        $this->assertSame($user->id, WatermarkCode::resolve(strtolower($code))?->id);
        $this->assertSame($user->id, WatermarkCode::resolve('  wm-'.strtolower($code).' ')?->id);
    }

    public function test_unknown_code_resolves_to_nothing(): void
    {
        User::factory()->create();

        $this->assertNull(WatermarkCode::resolve('ZZZZZZ'));
        $this->assertNull(WatermarkCode::resolve(''));
    }

    public function test_code_survives_the_user_changing_their_email_and_name(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com', 'name' => 'Old Name']);
        $code = WatermarkCode::for($user->id);

        $user->update(['email' => 'new@example.com', 'name' => 'New Name']);

        // The point of the code: a leak stamped months ago still points at this account.
        $this->assertSame($code, WatermarkCode::for($user->id));
        $this->assertSame($user->id, WatermarkCode::resolve($code)?->id);
    }

    public function test_admin_can_identify_a_code(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $leaker = User::factory()->create(['name' => 'Juan', 'email' => 'juan@example.com']);

        Livewire::actingAs($admin)->test(CourseManager::class)
            ->set('wm_lookup', 'WM-'.WatermarkCode::for($leaker->id))
            ->call('findWatermarkCode')
            ->assertSee('juan@example.com')
            ->assertSee('Juan');
    }

    public function test_admin_lookup_reports_an_unknown_code(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);

        Livewire::actingAs($admin)->test(CourseManager::class)
            ->set('wm_lookup', 'ZZZZZZ')
            ->call('findWatermarkCode')
            ->assertSet('wm_found', null)
            ->assertSee('No account matches');
    }
}
