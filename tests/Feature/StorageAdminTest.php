<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorageAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Plan::create(['name' => 'Free', 'slug' => 'free', 'daily_quota' => 20, 'monthly_quota' => 300]);
    }

    public function test_non_admin_cannot_access_storage(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);

        $this->actingAs($user)->get(route('admin.storage'))->assertForbidden();
    }

    public function test_admin_sees_storage_page(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);

        $this->actingAs($admin)->get(route('admin.storage'))
            ->assertOk()
            ->assertSee('Disk usage')
            ->assertSee('Available');
    }
}
