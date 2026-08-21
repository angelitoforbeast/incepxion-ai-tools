<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    private const FB = 'https://www.facebook.com/uvnis92jfzsg';

    protected function setUp(): void
    {
        parent::setUp();
        Plan::create(['name' => 'Free', 'slug' => 'free', 'daily_quota' => 20, 'monthly_quota' => 300]);
    }

    public function test_landing_page_loads_for_visitors(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Build systems that', false)
            ->assertSee('print profit', false);
    }

    public function test_all_fifteen_modules_are_listed(): void
    {
        $res = $this->get('/')->assertOk();

        // Spot-check the ends and a middle one, then count the cards.
        $res->assertSee('Andromeda Update')
            ->assertSee('The Evian Method')
            ->assertSee('Create Website');

        $this->assertSame(15, substr_count($res->getContent(), 'class="mod reveal'));
    }

    public function test_enrolment_points_at_the_facebook_profile(): void
    {
        $this->get('/')->assertOk()->assertSee(self::FB, false);
    }

    public function test_visitors_are_offered_login_and_members_their_dashboard(): void
    {
        $this->get('/')->assertOk()->assertSee(route('login'), false);

        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee(route('dashboard'), false);
    }

    public function test_the_page_works_without_javascript(): void
    {
        // The reveal animation hides content until JS runs, so a no-JS fallback has to
        // exist or the page would look blank to crawlers and anyone blocking scripts.
        $this->get('/')->assertOk()->assertSee('<noscript>', false);
    }

    public function test_previous_landing_page_is_still_reachable(): void
    {
        $this->get('/classic')
            ->assertOk()
            ->assertSee('All your tools', false);
    }
}
