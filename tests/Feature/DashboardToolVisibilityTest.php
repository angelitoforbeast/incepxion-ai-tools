<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardToolVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Plan::create(['name' => 'Free', 'slug' => 'free', 'daily_quota' => 20, 'monthly_quota' => 300]);

        Tool::create([
            'slug' => 'rts-processor', 'name' => 'RTS Processor', 'description' => 'J&T imports.',
            'icon' => '📦', 'is_active' => true, 'show_on_dashboard' => false, 'config' => [],
        ]);
        Tool::create([
            'slug' => 'ad-copy-generator', 'name' => 'AI Ad Copy Generator', 'description' => 'Ad copy.',
            'icon' => '📣', 'is_active' => true, 'show_on_dashboard' => true, 'config' => [],
        ]);

        $this->user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
    }

    public function test_hidden_tool_is_not_listed_on_the_dashboard(): void
    {
        $this->actingAs($this->user)->get('/dashboard')
            ->assertOk()
            ->assertSee('AI Ad Copy Generator')
            ->assertDontSee('RTS Processor');
    }

    public function test_hidden_tool_is_still_reachable_by_url(): void
    {
        $this->actingAs($this->user)->get('/tools/rts-processor')->assertOk();
        $this->actingAs($this->user)->get('/tools/rts-processor/monitoring')->assertOk();
        $this->actingAs($this->user)->get('/tools/rts-processor/remittance')->assertOk();
    }

    public function test_unhiding_puts_it_back_on_the_dashboard(): void
    {
        Tool::where('slug', 'rts-processor')->update(['show_on_dashboard' => true]);

        $this->actingAs($this->user)->get('/dashboard')
            ->assertOk()
            ->assertSee('RTS Processor');
    }
}
