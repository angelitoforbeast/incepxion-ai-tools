<?php

namespace Tests\Feature;

use App\Livewire\Admin\GenerationLog;
use App\Models\Generation;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GenerationLogTest extends TestCase
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

    private function gen(User $user, array $attrs = []): Generation
    {
        // created_at isn't fillable, so it has to be forced on after the insert — passing it
        // to create() silently leaves every row stamped "now", which quietly defeats any
        // date-range assertion.
        $when = $attrs['created_at'] ?? null;
        unset($attrs['created_at']);

        $gen = Generation::create(array_merge([
            'user_id'  => $user->id,
            'provider' => 'openai',
            'model'    => 'gpt-4o',
            'status'   => 'success',
            'input'    => ['product_name' => 'Vitamin C Serum', 'tone' => 'Friendly'],
            'output'   => ['variants' => []],
        ], $attrs));

        if ($when) {
            $gen->forceFill(['created_at' => $when])->save();
        }

        return $gen;
    }

    public function test_filtering_by_user(): void
    {
        $this->gen($this->alice, ['input' => ['product_name' => 'Alice Serum']]);
        $this->gen($this->bob, ['input' => ['product_name' => 'Bob Shampoo']]);

        Livewire::actingAs($this->admin)->test(GenerationLog::class)
            ->set('userId', (string) $this->bob->id)
            ->assertSee('Bob Shampoo')
            ->assertDontSee('Alice Serum');
    }

    public function test_filtering_by_status_and_model(): void
    {
        $this->gen($this->alice, ['status' => 'error', 'input' => ['product_name' => 'Broken One']]);
        $this->gen($this->alice, ['model' => 'gpt-4o-mini', 'input' => ['product_name' => 'Mini One']]);

        Livewire::actingAs($this->admin)->test(GenerationLog::class)
            ->set('status', 'error')
            ->assertSee('Broken One')
            ->assertDontSee('Mini One');

        Livewire::actingAs($this->admin)->test(GenerationLog::class)
            ->set('model', 'gpt-4o-mini')
            ->assertSee('Mini One')
            ->assertDontSee('Broken One');
    }

    public function test_searching_reaches_into_the_stored_input_json(): void
    {
        // The product name is only inside the JSON column, so this is the one filter that
        // depends on JSON extraction working on whichever database is behind it.
        $this->gen($this->alice, ['input' => ['product_name' => 'Cool Menthol Shampoo']]);
        $this->gen($this->bob, ['input' => ['product_name' => 'Vitamin C Serum']]);

        Livewire::actingAs($this->admin)->test(GenerationLog::class)
            ->set('search', 'Menthol')
            ->assertSee('Cool Menthol Shampoo')
            ->assertDontSee('Vitamin C Serum');
    }

    public function test_filtering_by_date_range(): void
    {
        $this->gen($this->alice, ['input' => ['product_name' => 'Old One'], 'created_at' => '2026-08-01 09:00:00']);
        $this->gen($this->alice, ['input' => ['product_name' => 'New One'], 'created_at' => '2026-08-20 09:00:00']);

        Livewire::actingAs($this->admin)->test(GenerationLog::class)
            ->set('from', '2026-08-15')
            ->set('to', '2026-08-25')
            ->assertSee('New One')
            ->assertDontSee('Old One');
    }

    public function test_clear_filters_brings_everything_back(): void
    {
        $this->gen($this->alice, ['input' => ['product_name' => 'Alice Serum']]);
        $this->gen($this->bob, ['input' => ['product_name' => 'Bob Shampoo']]);

        Livewire::actingAs($this->admin)->test(GenerationLog::class)
            ->set('userId', (string) $this->alice->id)
            ->set('search', 'Serum')
            ->call('clearFilters')
            ->assertSet('userId', '')
            ->assertSet('search', '')
            ->assertSee('Alice Serum')
            ->assertSee('Bob Shampoo');
    }

    public function test_inputs_are_shown_as_labelled_rows_not_raw_json(): void
    {
        $log = $this->gen($this->alice, ['input' => [
            'product_name'        => 'Cool Menthol Shampoo',
            'tone'                => 'Friendly at persuasive',
            'audience'            => '',
            'sales_prompt_fields' => ['STORE_NAME' => 'Flashlight', 'PRODUCT_PRICE' => 'P299'],
        ]]);

        $html = Livewire::actingAs($this->admin)->test(GenerationLog::class)
            ->call('toggle', $log->id)
            ->html();

        // Field names read as words, and the nested block gets its own heading.
        $this->assertStringContainsString('Product name', $html);
        $this->assertStringContainsString('Store name', $html);
        $this->assertStringContainsString('Sales prompt fields', $html);

        // The old pretty-printed JSON dump is gone.
        $this->assertStringNotContainsString('"product_name":', $html);
        $this->assertStringNotContainsString('"sales_prompt_fields":', $html);
    }
}
