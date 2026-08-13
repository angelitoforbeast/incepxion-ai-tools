<?php

namespace Tests\Feature;

use App\Livewire\Admin\RtsData;
use App\Models\FromJnt;
use App\Models\Plan;
use App\Models\RtsUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RtsDataAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Plan::create(['name' => 'Free', 'slug' => 'free', 'daily_quota' => 20, 'monthly_quota' => 300]);
    }

    private function seedRts(User $user, int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            FromJnt::create([
                'user_id'        => $user->id,
                'waybill_number' => "WB{$user->id}-{$i}",
                'status'         => 'Delivered',
            ]);
        }
        RtsUpload::create([
            'user_id'       => $user->id,
            'original_name' => 'sample.xlsx',
            'disk'          => 'local',
            'path'          => "rts/{$user->id}-sample.xlsx",
            'status'        => 'done',
        ]);
    }

    public function test_non_admin_cannot_access(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);

        $this->actingAs($user)->get('/admin/rts-data')->assertForbidden();
    }

    public function test_admin_sees_users_with_rts_data(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $seller = User::factory()->create(['status' => 'approved', 'name' => 'Seller One', 'email_verified_at' => now()]);
        $this->seedRts($seller, 3);

        $this->actingAs($admin)->get('/admin/rts-data')->assertOk()->assertSee('Seller One');
    }

    public function test_delete_requires_typed_confirmation(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $seller = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $this->seedRts($seller, 3);

        Livewire::actingAs($admin)->test(RtsData::class)
            ->call('confirmDelete', $seller->id)
            ->set('deleteConfirm', 'nope')
            ->call('deleteData')
            ->assertHasErrors('deleteConfirm');

        $this->assertSame(3, FromJnt::where('user_id', $seller->id)->count());
    }

    public function test_delete_removes_only_that_users_data(): void
    {
        $admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $a = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $b = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $this->seedRts($a, 3);
        $this->seedRts($b, 2);

        Livewire::actingAs($admin)->test(RtsData::class)
            ->call('confirmDelete', $a->id)
            ->set('deleteConfirm', 'DELETE')
            ->call('deleteData')
            ->assertDispatched('notify');

        $this->assertSame(0, FromJnt::where('user_id', $a->id)->count());
        $this->assertSame(0, RtsUpload::where('user_id', $a->id)->count());
        // Other user's data is untouched.
        $this->assertSame(2, FromJnt::where('user_id', $b->id)->count());
    }
}
