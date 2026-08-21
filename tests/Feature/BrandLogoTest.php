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
            ->assertSee('Incepxion Services Inc.', false);
    }

    public function test_login_and_register_show_the_logo(): void
    {
        $this->get('/login')->assertOk()->assertSee('logo.png', false);
        $this->get('/register')->assertOk()->assertSee('logo.png', false);
    }

    public function test_dark_surfaces_use_the_light_variant(): void
    {
        // Half the wordmark is near-black; on a dark surface the plain file would be
        // half-invisible, so those placements must reach for the light variant.
        $this->assertFileExists(public_path('logo-light.png'));

        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('logo-light.png', false);

        $this->get('/')->assertOk()->assertSee('logo-light.png', false);
    }

    public function test_light_surfaces_use_the_plain_mark(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('logo.png', false)
            ->assertDontSee('logo-light.png', false);
    }

    public function test_the_light_variant_is_actually_light(): void
    {
        $img = imagecreatefrompng(public_path('logo-light.png'));
        $w = imagesx($img);
        $h = imagesy($img);

        $lit = 0;
        $opaque = 0;
        for ($y = 0; $y < $h; $y += 4) {
            for ($x = 0; $x < $w; $x += 4) {
                $c = imagecolorat($img, $x, $y);
                if ((($c >> 24) & 0x7F) > 60) {
                    continue;                       // transparent
                }
                $opaque++;
                $avg = ((($c >> 16) & 0xFF) + (($c >> 8) & 0xFF) + ($c & 0xFF)) / 3;
                if ($avg > 128) {
                    $lit++;
                }
            }
        }

        $this->assertGreaterThan(0, $opaque);
        // Most of the ink has to be brighter than mid-grey or it won't read on slate-900.
        $this->assertGreaterThan(0.8, $lit / $opaque);
    }
}
