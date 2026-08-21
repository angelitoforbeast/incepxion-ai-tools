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
            ->assertSee('BUILD A SYSTEM', false)
            ->assertSee('THAT PRINTS PROFIT', false);
    }

    public function test_the_offer_is_stated(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('₱49,500', false)
            ->assertSee('₱75,000', false)
            ->assertSee('FREE 1-Year IncepXion Website Subscription', false);
    }

    public function test_roadmap_image_is_served_as_a_file_with_a_webp_alternative(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('images/roadmap.png', false)
            ->assertSee('images/roadmap.webp', false);

        // Both files must actually exist, or the section renders as a broken image.
        $this->assertFileExists(public_path('images/roadmap.png'));
        $this->assertFileExists(public_path('images/roadmap.webp'));

        // The whole point of the WebP is weight — guard against it being replaced by
        // something as heavy as the PNG.
        $this->assertLessThan(600 * 1024, filesize(public_path('images/roadmap.webp')));
    }

    public function test_every_call_to_action_reaches_the_facebook_profile(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertGreaterThanOrEqual(4, substr_count($html, self::FB));
    }

    public function test_visitors_are_offered_login_and_members_their_dashboard(): void
    {
        $this->get('/')->assertOk()->assertSee(route('login'), false);

        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee(route('dashboard'), false)
            ->assertDontSee(route('login'), false);
    }

    public function test_the_page_is_not_blank_without_javascript(): void
    {
        // Sections start at opacity 0 and are revealed by script, so a no-JS fallback has
        // to exist or the page shows nothing at all.
        $this->get('/')->assertOk()->assertSee('<noscript>', false);
    }

    public function test_previous_landing_page_is_still_reachable(): void
    {
        $this->get('/classic')
            ->assertOk()
            ->assertSee('All your tools', false);
    }
}
