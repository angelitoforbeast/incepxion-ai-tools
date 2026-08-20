<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every page used to render the same <title>, so browser tabs were indistinguishable.
 * These lock in that each page names itself.
 */
class PageTitleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Plan::create(['name' => 'Free', 'slug' => 'free', 'daily_quota' => 20, 'monthly_quota' => 300]);
    }

    private function approved(array $attrs = []): User
    {
        return User::factory()->create(array_merge(
            ['status' => 'approved', 'email_verified_at' => now()],
            $attrs
        ));
    }

    public static function pages(): array
    {
        return [
            'dashboard'  => ['/dashboard', 'Dashboard — Incepxion'],
            'rts'        => ['/tools/rts-processor', 'RTS Processor — Incepxion'],
            'monitoring' => ['/tools/rts-processor/monitoring', 'RTS Monitoring — Incepxion'],
            'remittance' => ['/tools/rts-processor/remittance', 'Remittance — Incepxion'],
            'ad copy'    => ['/tools/ad-copy-generator', 'Ad Copy Generator — Incepxion'],
            'profile'    => ['/profile', 'Profile — Incepxion'],
            'settings'   => ['/settings', 'Settings — Incepxion'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_page_has_its_own_title(string $url, string $expected): void
    {
        $this->actingAs($this->approved())->get($url)
            ->assertOk()
            ->assertSee('<title>'.$expected.'</title>', false);
    }

    public function test_admin_pages_are_labelled_as_admin(): void
    {
        $admin = $this->approved(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.users'))
            ->assertOk()
            ->assertSee('<title>Admin · Users — Incepxion</title>', false);

        $this->actingAs($admin)->get(route('admin.errors'))
            ->assertOk()
            ->assertSee('<title>Admin · Error Logs — Incepxion</title>', false);
    }

    public function test_login_page_names_itself(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('<title>Log in — Incepxion</title>', false);
    }
}
