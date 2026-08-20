<?php

namespace Tests\Feature;

use App\Livewire\AdCopyGenerator;
use App\Models\Generation;
use App\Models\Plan;
use App\Models\Tool;
use App\Models\User;
use App\Models\UserApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Phase2Test extends TestCase
{
    use RefreshDatabase;

    protected Plan $free;

    protected function setUp(): void
    {
        parent::setUp();

        $this->free = Plan::create([
            'name' => 'Free', 'slug' => 'free', 'daily_quota' => 20, 'monthly_quota' => 300,
        ]);

        Tool::create([
            'slug' => 'ad-copy-generator', 'name' => 'AI Ad Copy Generator',
            'description' => 'Facebook ad copy.', 'icon' => '📣', 'is_active' => true,
            'config' => ['provider' => 'openai', 'default_model' => 'gpt-4o'],
        ]);
    }

    private function approvedUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'status' => 'approved', 'email_verified_at' => now(), 'plan_id' => $this->free->id,
        ], $attrs));
    }

    public function test_dashboard_lists_the_ad_copy_tool(): void
    {
        $this->actingAs($this->approvedUser())->get('/dashboard')
            ->assertOk()
            ->assertSee('AI Ad Copy Generator');
    }

    public function test_pending_user_cannot_open_the_tool(): void
    {
        $user = User::factory()->create(['status' => 'pending', 'email_verified_at' => now()]);

        $this->actingAs($user)->get('/tools/ad-copy-generator')
            ->assertRedirect(route('approval.pending'));
    }

    public function test_tool_page_renders_for_approved_user(): void
    {
        $this->actingAs($this->approvedUser())->get('/tools/ad-copy-generator')
            ->assertOk()
            ->assertSee('Ad Copy Generator');
    }

    public function test_generate_without_api_key_shows_error(): void
    {
        $user = $this->approvedUser();

        Livewire::actingAs($user)->test(AdCopyGenerator::class)
            ->set('product_name', 'Test Serum')
            ->set('product_description', 'Vitamin C serum, 299 pesos')
            ->set('sp.STORE_NAME', 'MyShop')
            ->set('sp.PRODUCT_PRICE', 'P299')
            ->set('sp.PROMO', 'Buy 1 Take 1')
            ->call('generate')
            ->assertSet('error', fn ($e) => str_contains($e, 'OpenAI API key'));
    }

    public function test_generate_requires_shop_name_price_and_promo(): void
    {
        $user = $this->approvedUser();

        Livewire::actingAs($user)->test(AdCopyGenerator::class)
            ->set('product_name', 'Test')
            ->set('product_description', 'A description')
            ->set('sp.STORE_NAME', '')
            ->set('sp.PRODUCT_PRICE', '')
            ->set('sp.PROMO', '')
            ->call('generate')
            ->assertHasErrors(['sp.STORE_NAME', 'sp.PRODUCT_PRICE', 'sp.PROMO']);
    }

    public function test_user_can_save_sales_prompt_defaults(): void
    {
        $user = $this->approvedUser();

        Livewire::actingAs($user)->test(AdCopyGenerator::class)
            ->set('sp.STORE_NAME', 'MyShop')
            ->set('sp.PAYMENT_METHOD', 'GCash')
            ->set('tone', 'Bold')
            ->call('saveDefaults');

        $saved = $user->fresh()->sp_defaults;
        $this->assertSame('MyShop', $saved['sp']['STORE_NAME']);
        $this->assertSame('GCash', $saved['sp']['PAYMENT_METHOD']);
        $this->assertSame('Bold', $saved['tone']);
    }

    public function test_system_and_saved_defaults_apply_on_mount(): void
    {
        $user = $this->approvedUser();
        $user->update(['sp_defaults' => ['tone' => 'Bold', 'sp' => ['STORE_NAME' => 'SavedShop']]]);

        Livewire::actingAs($user)->test(AdCopyGenerator::class)
            ->assertSet('sp.STORE_NAME', 'SavedShop')      // user's saved default
            ->assertSet('sp.PAYMENT_METHOD', 'COD')        // system default
            ->assertSet('tone', 'Bold');                   // saved ad-field default
    }

    public function test_sales_prompt_service_fills_placeholders(): void
    {
        $svc = new \App\Services\SalesPromptService();
        $out = $svc->fill('Store: {{STORE_NAME}} · Price: {{PRODUCT_PRICE}}', [
            'STORE_NAME' => 'MyShop', 'PRODUCT_PRICE' => 'P299',
        ]);

        $this->assertStringContainsString('Store: MyShop', $out);
        $this->assertStringContainsString('Price: P299', $out);
    }

    public function test_copy_is_recorded_on_the_generation(): void
    {
        $user = $this->approvedUser();
        $gen = Generation::create(['user_id' => $user->id, 'provider' => 'openai', 'status' => 'success', 'output' => []]);

        Livewire::actingAs($user)->test(AdCopyGenerator::class)
            ->set('lastGenerationId', $gen->id)
            ->set('results', [[
                'angle' => 'x', 'headline' => 'Hi', 'primary_text' => 'Buy now',
                'messaging_template' => 'Hello', 'quick_replies' => ['a', 'b', 'c'],
            ]])
            ->call('recordCopy', 0, 'primary_text');

        $copies = $gen->fresh()->copies;
        $this->assertCount(1, $copies);
        $this->assertSame('primary_text', $copies[0]['field']);
        $this->assertSame('Buy now', $copies[0]['text']);
    }

    // Note: daily-quota enforcement was intentionally removed (unlimited generation,
    // users bring their own OpenAI key), so the old over-quota guard test no longer applies.

    public function test_generate_rejects_oversized_sales_prompt_field(): void
    {
        $user = $this->approvedUser();

        Livewire::actingAs($user)->test(AdCopyGenerator::class)
            ->set('product_name', 'Test')
            ->set('product_description', 'A description')
            ->set('sp.STORE_NAME', 'MyShop')
            ->set('sp.PRODUCT_PRICE', 'P299')
            ->set('sp.PROMO', str_repeat('x', 600))   // PROMO cap is 500
            ->call('generate')
            ->assertHasErrors(['sp.PROMO']);
    }

    public function test_generate_allows_field_at_the_limit(): void
    {
        $user = $this->approvedUser();

        // At exactly the cap, the length rule passes (fails later only on the missing API key).
        Livewire::actingAs($user)->test(AdCopyGenerator::class)
            ->set('product_name', 'Test')
            ->set('product_description', 'A description')
            ->set('sp.STORE_NAME', 'MyShop')
            ->set('sp.PRODUCT_PRICE', 'P299')
            ->set('sp.PROMO', str_repeat('x', 500))
            ->call('generate')
            ->assertHasNoErrors(['sp.PROMO']);
    }

    public function test_save_defaults_rejects_oversized_field(): void
    {
        $user = $this->approvedUser();

        Livewire::actingAs($user)->test(AdCopyGenerator::class)
            ->set('sp.LEGITIMACY_INFO', str_repeat('x', 1200))   // cap is 1000
            ->call('saveDefaults')
            ->assertHasErrors(['sp.LEGITIMACY_INFO']);

        $this->assertNull($user->fresh()->sp_defaults);
    }

    public function test_playground_rejects_oversized_message(): void
    {
        $user = $this->approvedUser();

        Livewire::actingAs($user)->test(AdCopyGenerator::class)
            ->set('salesTestInput', str_repeat('x', 2100))   // cap is 2000
            ->call('testSales')
            ->assertHasErrors(['salesTestInput']);
    }
}
