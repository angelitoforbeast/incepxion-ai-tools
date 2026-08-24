<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Every page under the admin prefix, checked against every kind of visitor.
 *
 * Written to enumerate the routes rather than list them by hand: a new admin page added
 * outside the protected group would otherwise ship unguarded and no test would notice.
 */
class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Plan::create(['name' => 'Free', 'slug' => 'free', 'daily_quota' => 20, 'monthly_quota' => 300]);

        // The Prompts page loads this row with firstOrFail, so without it that page 404s
        // and the sweep below would report a break that production doesn't have.
        \App\Models\Tool::create([
            'slug' => 'ad-copy-generator', 'name' => 'AI Ad Copy Generator',
            'description' => 'Facebook ad copy.', 'icon' => '📣', 'is_active' => true, 'config' => [],
        ]);
    }

    /** @return array<string,string> route name => url, for every GET page under admin */
    private function adminRoutes(): array
    {
        $urls = [];
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if ($name && str_starts_with($name, 'admin.') && in_array('GET', $route->methods(), true)) {
                $urls[$name] = '/'.ltrim($route->uri(), '/');
            }
        }
        ksort($urls);

        return $urls;
    }

    public function test_there_are_admin_routes_to_check(): void
    {
        // If this ever hits zero the rest of the file would pass while testing nothing.
        $this->assertGreaterThanOrEqual(10, count($this->adminRoutes()));
    }

    public function test_guests_are_sent_to_login(): void
    {
        foreach ($this->adminRoutes() as $name => $url) {
            $this->get($url)->assertRedirect(route('login'), "Guest reached {$name}");
        }
    }

    public function test_the_bare_prefix_is_closed_to_guests(): void
    {
        $this->get('/console-7k29fx')->assertRedirect(route('login'));
    }

    public function test_ordinary_members_are_refused(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);

        foreach ($this->adminRoutes() as $name => $url) {
            $this->actingAs($user)->get($url)
                ->assertStatus(403, "A plain member reached {$name}");
        }
    }

    public function test_a_pending_account_is_refused(): void
    {
        $user = User::factory()->create(['status' => 'pending']);

        foreach ($this->adminRoutes() as $name => $url) {
            $this->actingAs($user)->get($url)
                ->assertStatus(403, "A pending user reached {$name}");
        }
    }

    public function test_a_suspended_admin_still_gets_in(): void
    {
        // Recording today's behaviour rather than claiming it is right: the gate is the
        // role alone, so taking the role away is what removes access, not suspending.
        $admin = User::factory()->create(['status' => 'suspended', 'role' => 'admin', 'email_verified_at' => now()]);

        $this->actingAs($admin)->get(route('admin.users'))->assertOk();
    }

    public function test_admins_can_open_every_page(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);

        $broken = [];
        foreach ($this->adminRoutes() as $name => $url) {
            $status = $this->actingAs($admin)->get($url)->getStatusCode();
            if ($status !== 200) {
                $broken[] = "{$name} ({$url}) → {$status}";
            }
        }

        $this->assertSame([], $broken, "Admin pages not loading:\n".implode("\n", $broken));
    }

    public function test_the_bare_prefix_sends_admins_to_users(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);

        $this->actingAs($admin)->get('/console-7k29fx')->assertRedirect(route('admin.users'));
    }
}
