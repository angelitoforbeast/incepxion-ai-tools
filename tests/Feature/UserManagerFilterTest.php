<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserManager;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The filter row above the user list.
 *
 * Most of these tabs pick a status, but 'admin' picks a role, so the query has to branch —
 * filtering on status='admin' matches nobody, and the tab would come back empty while
 * looking like it worked. That silent version is the reason this file exists.
 */
class UserManagerFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Plan::create(['name' => 'Free', 'slug' => 'free', 'daily_quota' => 20, 'monthly_quota' => 300]);

        $this->admin = User::factory()->create([
            'name' => 'Owner', 'status' => 'approved', 'role' => 'admin', 'email_verified_at' => now(),
        ]);
    }

    private function member(string $name, string $status, string $role = 'user'): User
    {
        return User::factory()->create([
            'name' => $name, 'status' => $status, 'role' => $role, 'email_verified_at' => now(),
        ]);
    }

    public function test_the_suspended_tab_shows_suspended_members(): void
    {
        $this->member('Sus Pended', 'suspended');
        $this->member('App Roved', 'approved');

        Livewire::actingAs($this->admin)->test(UserManager::class)
            ->set('filter', 'suspended')
            ->assertSee('Sus Pended')
            ->assertDontSee('App Roved');
    }

    public function test_the_admin_tab_shows_admins(): void
    {
        $this->member('Second Owner', 'approved', 'admin');
        $this->member('Plain Member', 'approved');

        Livewire::actingAs($this->admin)->test(UserManager::class)
            ->set('filter', 'admin')
            ->assertSee('Second Owner')
            ->assertSee('Owner')
            ->assertDontSee('Plain Member');
    }

    public function test_the_admin_tab_is_not_read_as_a_status(): void
    {
        // where('status', 'admin') matches nothing, so the tab would look like it worked
        // and quietly show an empty list. Nobody has that status, so seeing any row at all
        // proves the query branched on role instead.
        $this->member('Plain Member', 'approved');

        Livewire::actingAs($this->admin)->test(UserManager::class)
            ->set('filter', 'admin')
            ->assertSee('Owner')
            ->assertDontSee('No users in this filter');
    }

    public function test_an_admin_who_is_not_approved_still_appears(): void
    {
        // The tab answers "who can reach the console", and EnsureAdmin checks the role
        // alone — so a suspended admin is exactly the row an owner needs to find.
        $this->member('Suspended Owner', 'suspended', 'admin');

        Livewire::actingAs($this->admin)->test(UserManager::class)
            ->set('filter', 'admin')
            ->assertSee('Suspended Owner');
    }

    public function test_the_status_tabs_still_work(): void
    {
        $this->member('Pen Ding', 'pending');
        $this->member('Re Jected', 'rejected');

        $page = Livewire::actingAs($this->admin)->test(UserManager::class);

        $page->set('filter', 'pending')->assertSee('Pen Ding')->assertDontSee('Re Jected');
        $page->set('filter', 'rejected')->assertSee('Re Jected')->assertDontSee('Pen Ding');
    }

    public function test_all_shows_every_status(): void
    {
        $this->member('Pen Ding', 'pending');
        $this->member('Sus Pended', 'suspended');
        $this->member('Re Jected', 'rejected');

        Livewire::actingAs($this->admin)->test(UserManager::class)
            ->set('filter', 'all')
            ->assertSee('Pen Ding')
            ->assertSee('Sus Pended')
            ->assertSee('Re Jected')
            ->assertSee('Owner');
    }

    public function test_search_still_narrows_within_a_filter(): void
    {
        $this->member('Alpha Person', 'suspended');
        $this->member('Beta Person', 'suspended');

        Livewire::actingAs($this->admin)->test(UserManager::class)
            ->set('filter', 'suspended')
            ->set('search', 'Alpha')
            ->assertSee('Alpha Person')
            ->assertDontSee('Beta Person');
    }

    public function test_every_tab_in_the_row_returns_a_working_page(): void
    {
        // A tab whose key does not match anything the query understands would 500 or come
        // back blank; this walks the same list the blade renders.
        foreach (['pending', 'approved', 'rejected', 'suspended', 'admin', 'all'] as $tab) {
            Livewire::actingAs($this->admin)->test(UserManager::class)
                ->set('filter', $tab)
                ->assertOk();
        }
    }
}
