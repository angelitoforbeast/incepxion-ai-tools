<?php

namespace Tests\Feature;

use App\Livewire\Admin\AccessLog as AccessLogComponent;
use App\Models\AccessLog;
use App\Models\User;
use App\Services\GeoIp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccessLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_signout_is_logged(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'current_session_id' => 'another-device']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));

        $this->assertDatabaseHas('access_logs', [
            'user_id' => $user->id,
            'type'    => 'device_signout',
        ]);
    }

    public function test_geoip_returns_local_for_loopback_without_network(): void
    {
        $this->assertSame('Local network', GeoIp::locate('127.0.0.1'));
    }

    public function test_geoip_null_for_blank(): void
    {
        $this->assertNull(GeoIp::locate(null));
    }

    public function test_device_label_parsing(): void
    {
        $chromeWin = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36';
        $this->assertSame('Chrome on Windows', AccessLog::formatDevice($chromeWin));
        $this->assertSame('—', AccessLog::formatDevice(null));
    }

    public function test_admin_access_log_filters_by_event_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        AccessLog::create(['user_id' => $admin->id, 'type' => 'login', 'ip_address' => '1.1.1.1', 'user_agent' => 'x', 'location' => 'Manila, PH']);
        AccessLog::create(['user_id' => $admin->id, 'type' => 'device_signout', 'ip_address' => '2.2.2.2', 'user_agent' => 'x', 'location' => 'Cebu, PH']);

        Livewire::actingAs($admin)->test(AccessLogComponent::class)
            ->assertSee('1.1.1.1')
            ->assertSee('2.2.2.2')
            ->set('type', 'login')
            ->assertSee('1.1.1.1')
            ->assertDontSee('2.2.2.2');
    }
}
