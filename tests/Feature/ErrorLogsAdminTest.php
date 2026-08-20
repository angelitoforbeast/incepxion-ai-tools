<?php

namespace Tests\Feature;

use App\Livewire\Admin\ErrorLogs;
use App\Models\ErrorLog;
use App\Models\Plan;
use App\Models\User;
use App\Support\ErrorLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ErrorLogsAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Plan::create(['name' => 'Free', 'slug' => 'free', 'daily_quota' => 20, 'monthly_quota' => 300]);
    }

    public function test_non_admin_cannot_access(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $this->actingAs($user)->get(route('admin.errors'))->assertForbidden();
    }

    public function test_admin_sees_error_logs_page(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $this->actingAs($admin)->get(route('admin.errors'))->assertOk()->assertSee('Error Logs');
    }

    public function test_capture_stores_a_real_error(): void
    {
        ErrorLogger::capture(new \RuntimeException('boom test error'));

        $this->assertDatabaseHas('error_logs', ['message' => 'boom test error']);
    }

    public function test_capture_skips_validation_exceptions(): void
    {
        ErrorLogger::capture(ValidationException::withMessages(['field' => 'bad']));

        $this->assertSame(0, ErrorLog::count());
    }

    public function test_admin_can_clear_logs(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        ErrorLogger::capture(new \RuntimeException('x'));
        $this->assertGreaterThan(0, ErrorLog::count());

        Livewire::actingAs($admin)->test(ErrorLogs::class)->call('clearLogs');

        $this->assertSame(0, ErrorLog::count());
    }
}
