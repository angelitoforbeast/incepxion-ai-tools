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
            ->call('generate')
            ->assertSet('error', fn ($e) => str_contains($e, 'OpenAI API key'));
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

    public function test_generate_over_quota_shows_error_without_calling_api(): void
    {
        $zeroPlan = Plan::create(['name' => 'Zero', 'slug' => 'zero', 'daily_quota' => 0, 'monthly_quota' => 0]);
        $user = $this->approvedUser(['plan_id' => $zeroPlan->id]);

        $key = new UserApiKey(['user_id' => $user->id, 'provider' => 'openai']);
        $key->setKey('sk-dummy-key-for-guard-test');
        $key->save();

        Livewire::actingAs($user)->test(AdCopyGenerator::class)
            ->set('product_name', 'Test Serum')
            ->set('product_description', 'Vitamin C serum, 299 pesos')
            ->call('generate')
            ->assertSet('error', fn ($e) => str_contains($e, 'daily limit'));
    }
}
