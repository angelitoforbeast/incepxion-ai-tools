<?php

namespace Tests\Feature;

use App\Livewire\Admin\VideoLog;
use App\Models\Plan;
use App\Models\User;
use App\Models\VideoView;
use App\Support\WatermarkCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VideoLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $alice;
    private User $bob;

    protected function setUp(): void
    {
        parent::setUp();
        Plan::create(['name' => 'Free', 'slug' => 'free', 'daily_quota' => 20, 'monthly_quota' => 300]);

        $this->admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $this->alice = User::factory()->create(['name' => 'Alice', 'status' => 'approved', 'email_verified_at' => now()]);
        $this->bob   = User::factory()->create(['name' => 'Bob', 'status' => 'approved', 'email_verified_at' => now()]);
    }

    private function logView(User $user, int $lessonId, string $ip, string $ua = 'Mozilla/5.0 (Windows NT 10.0)', ?string $at = null): VideoView
    {
        return VideoView::create([
            'user_id'        => $user->id,
            'course_id'      => 1,
            'lesson_id'      => $lessonId,
            'ip_address'     => $ip,
            'user_agent'     => $ua,
            'watermark_code' => WatermarkCode::for($user->id),
            'created_at'     => $at ? \Carbon\Carbon::parse($at) : now(),
        ]);
    }

    public function test_non_admin_cannot_access(): void
    {
        $this->actingAs($this->alice)->get(route('admin.video-log'))->assertForbidden();
    }

    public function test_admin_sees_recorded_views(): void
    {
        $this->logView($this->alice, 5, '1.1.1.1');

        $this->actingAs($this->admin)->get(route('admin.video-log'))
            ->assertOk()
            ->assertSee('Alice')
            ->assertSee('1.1.1.1');
    }

    public function test_recording_captures_who_where_and_the_watermark_code(): void
    {
        $row = $this->logView($this->alice, 7, '49.144.64.121', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0)');

        $this->assertSame($this->alice->id, $row->user_id);
        $this->assertSame('49.144.64.121', $row->ip_address);
        $this->assertSame(WatermarkCode::for($this->alice->id), $row->watermark_code);
        // The code on a leaked frame has to lead back to this row's account.
        $this->assertSame($this->alice->id, WatermarkCode::resolve($row->watermark_code)?->id);
    }

    public function test_device_label_is_derived_from_the_user_agent(): void
    {
        $this->assertSame('iPhone', $this->logView($this->alice, 1, '1.1.1.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0)')->device);
        $this->assertSame('Android', $this->logView($this->alice, 1, '1.1.1.1', 'Mozilla/5.0 (Linux; Android 14)')->device);
        $this->assertSame('Windows', $this->logView($this->alice, 1, '1.1.1.1', 'Mozilla/5.0 (Windows NT 10.0)')->device);
    }

    public function test_filtering_by_user_and_by_lesson(): void
    {
        $this->logView($this->alice, 5, '1.1.1.1');
        $this->logView($this->bob, 9, '2.2.2.2');

        Livewire::actingAs($this->admin)->test(VideoLog::class)
            ->set('userId', (string) $this->bob->id)
            ->assertSee('2.2.2.2')
            ->assertDontSee('1.1.1.1');

        Livewire::actingAs($this->admin)->test(VideoLog::class)
            ->set('lessonId', '5')
            ->assertSee('1.1.1.1')
            ->assertDontSee('2.2.2.2');
    }

    public function test_filtering_by_ip_prefix_and_date_range(): void
    {
        $this->logView($this->alice, 5, '49.144.64.121', 'Mozilla/5.0 (Windows NT 10.0)', '2026-08-01 09:00:00');
        $this->logView($this->bob, 5, '112.198.45.7', 'Mozilla/5.0 (Windows NT 10.0)', '2026-08-10 09:00:00');

        Livewire::actingAs($this->admin)->test(VideoLog::class)
            ->set('ip', '49.144')
            ->assertSee('49.144.64.121')
            ->assertDontSee('112.198.45.7');

        Livewire::actingAs($this->admin)->test(VideoLog::class)
            ->set('from', '2026-08-09')
            ->set('to', '2026-08-11')
            ->assertSee('112.198.45.7')
            ->assertDontSee('49.144.64.121');
    }

    public function test_account_watching_from_many_ips_is_flagged_as_shared(): void
    {
        foreach (['1.1.1.1', '2.2.2.2', '3.3.3.3'] as $ip) {
            $this->logView($this->alice, 5, $ip);
        }
        // Bob stays on one connection and must not be flagged.
        $this->logView($this->bob, 5, '4.4.4.4');
        $this->logView($this->bob, 6, '4.4.4.4');

        $sharing = Livewire::actingAs($this->admin)->test(VideoLog::class)->instance()->sharing;

        $this->assertCount(1, $sharing);
        $this->assertSame($this->alice->id, $sharing->first()->user_id);
        $this->assertSame(3, (int) $sharing->first()->ips);
    }

    public function test_sharing_alert_only_looks_at_the_last_day(): void
    {
        foreach (['1.1.1.1', '2.2.2.2', '3.3.3.3'] as $ip) {
            $this->logView($this->alice, 5, $ip, 'Mozilla/5.0 (Windows NT 10.0)', now()->subDays(3)->toDateTimeString());
        }

        $sharing = Livewire::actingAs($this->admin)->test(VideoLog::class)->instance()->sharing;

        $this->assertCount(0, $sharing);
    }

    public function test_focus_user_jumps_into_that_account(): void
    {
        $this->logView($this->alice, 5, '1.1.1.1');
        $this->logView($this->bob, 5, '2.2.2.2');

        Livewire::actingAs($this->admin)->test(VideoLog::class)
            ->set('lessonId', '5')
            ->call('focusUser', $this->alice->id)
            ->assertSet('userId', (string) $this->alice->id)
            ->assertSet('lessonId', '')      // other filters cleared so the history is complete
            ->assertSee('1.1.1.1')
            ->assertDontSee('2.2.2.2');
    }

    public function test_a_logging_failure_never_breaks_playback(): void
    {
        // user_id 999999 has no matching row, so the FK insert fails — record() must swallow it.
        VideoView::record(999999, 1, 1, 'ABC123');

        $this->assertSame(0, VideoView::count());
    }
}
