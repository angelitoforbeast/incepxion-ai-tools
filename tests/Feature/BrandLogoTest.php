<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandLogoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Plan::create(['name' => 'Free', 'slug' => 'free', 'daily_quota' => 20, 'monthly_quota' => 300]);
    }

    public function test_the_logo_file_exists_and_is_transparent(): void
    {
        $path = public_path('logo.png');
        $this->assertFileExists($path);

        // A JPEG re-saved as .png would carry its white box into every dark surface.
        $info = getimagesize($path);
        $this->assertSame('image/png', $info['mime']);

        $img = imagecreatefrompng($path);
        $corner = imagecolorat($img, 0, 0);
        $alpha = ($corner >> 24) & 0x7F;
        $this->assertSame(127, $alpha, 'The corner pixel should be fully transparent.');
    }

    public function test_dashboard_sidebar_shows_the_logo(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('logo.png', false)
            ->assertSee('Incepxion Services Inc.', false);
    }

    public function test_login_and_register_show_the_logo(): void
    {
        $this->get('/login')->assertOk()->assertSee('logo.png', false);
        $this->get('/register')->assertOk()->assertSee('logo.png', false);
    }

    public function test_landing_page_shows_the_logo(): void
    {
        $this->get('/')->assertOk()->assertSee('logo.png', false);
    }

    public function test_dark_placements_put_the_mark_on_a_light_plate(): void
    {
        // Half the wordmark is near-black, so on the dark sidebar it needs a light
        // background behind it or it simply vanishes.
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);

        $html = $this->actingAs($user)->get('/dashboard')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<span class="[^"]*bg-white[^"]*">\s*<img src="[^"]*logo\.png"/s',
            $html
        );
    }
}
