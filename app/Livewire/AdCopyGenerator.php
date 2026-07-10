<?php

namespace App\Livewire;

use App\Models\Generation;
use App\Models\Tool;
use App\Services\AdCopyService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class AdCopyGenerator extends Component
{
    #[Validate('required|string|max:200')]
    public string $product_name = '';

    #[Validate('required|string|max:4000')]
    public string $product_description = '';

    #[Validate('nullable|string|max:500')]
    public string $audience = '';

    #[Validate('required|in:Taglish,Filipino,English')]
    public string $language = 'Taglish';

    #[Validate('required|string|max:60')]
    public string $tone = 'Friendly at persuasive';

    #[Validate('required|integer|min:1|max:5')]
    public int $variants = 1;

    #[Validate('required|numeric|min:0|max:1')]
    public float $creativity = 0.7;

    public array $results = [];
    public ?string $error = null;
    public ?int $lastGenerationId = null;

    /** BotCake sales-prompt placeholder values, keyed by placeholder name. */
    public array $sp = [];
    public ?string $generatedPrompt = null;

    public function mount(): void
    {
        foreach (array_keys(\App\Services\SalesPromptService::FIELDS) as $key) {
            $this->sp[$key] = '';
        }
    }

    public function generate(AdCopyService $service): void
    {
        $this->validate();
        $this->error = null;
        $this->results = [];

        $user = auth()->user();

        if (! $user->apiKeyFor('openai')) {
            $this->error = 'You have no OpenAI API key yet. Add one in Settings before generating.';

            return;
        }

        if (! $user->hasQuotaLeft()) {
            $this->error = "You've reached your daily limit ({$user->dailyQuota()} generations). Come back tomorrow or upgrade your plan.";

            return;
        }

        $tool = Tool::where('slug', 'ad-copy-generator')->first();
        $start = microtime(true);

        try {
            $out = $service->generate($user, [
                'product_name'        => $this->product_name,
                'product_description' => $this->product_description,
                'audience'            => $this->audience,
                'language'            => $this->language,
                'tone'                => $this->tone,
                'variants'            => $this->variants,
                'creativity'          => $this->creativity,
                'model'               => $tool->config['default_model'] ?? 'gpt-4o',
                'system_prompt'       => $tool->config['system_prompt'] ?? null,
            ]);

            $this->results = $out['variants'];

            // Fill the BotCake sales-assistant prompt from the placeholder inputs.
            $values = $this->sp;
            $values['PRODUCT_NAME'] = ($values['PRODUCT_NAME'] ?? '') ?: $this->product_name;
            $values['PRODUCT_INFORMATION'] = ($values['PRODUCT_INFORMATION'] ?? '') ?: $this->product_description;
            $template = $tool->config['botcake_template'] ?? \App\Services\SalesPromptService::DEFAULT_TEMPLATE;
            $this->generatedPrompt = app(\App\Services\SalesPromptService::class)->fill($template, $values);

            $generation = Generation::create([
                'user_id'       => $user->id,
                'tool_id'       => $tool?->id,
                'provider'      => 'openai',
                'model'         => $out['model'],
                'input'         => [
                    'product_name'        => $this->product_name,
                    'product_description' => $this->product_description,
                    'audience'            => $this->audience,
                    'language'            => $this->language,
                    'tone'                => $this->tone,
                    'variants'            => $this->variants,
                    'sales_prompt_fields' => $values,
                ],
                'output'        => ['variants' => $out['variants'], 'sales_prompt' => $this->generatedPrompt],
                'input_tokens'  => $out['input_tokens'],
                'output_tokens' => $out['output_tokens'],
                'status'        => 'success',
                'duration_ms'   => (int) ((microtime(true) - $start) * 1000),
            ]);

            $this->lastGenerationId = $generation->id;
            $user->recordUsage();
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
            Log::error('Ad copy generation failed', ['user' => $user->id, 'msg' => $e->getMessage()]);

            Generation::create([
                'user_id'     => $user->id,
                'tool_id'     => $tool?->id,
                'provider'    => 'openai',
                'input'       => ['product_name' => $this->product_name],
                'status'      => 'error',
                'error'       => mb_substr($e->getMessage(), 0, 1000),
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            ]);
        }
    }

    /** Record that the user copied a specific field of a variant (fires from the Copy button). */
    public function recordCopy(int $index, string $field): void
    {
        if (! $this->lastGenerationId) {
            return;
        }

        $generation = \App\Models\Generation::find($this->lastGenerationId);
        if (! $generation || $generation->user_id !== auth()->id()) {
            return;
        }

        $text = $field === 'sales_prompt'
            ? $this->generatedPrompt
            : ($this->results[$index][$field] ?? null);

        $copies = $generation->copies ?? [];
        $copies[] = [
            'variant' => $index,
            'field'   => $field,
            'text'    => $text,
            'at'      => now()->toDateTimeString(),
        ];
        $generation->update(['copies' => $copies]);
    }

    public function render()
    {
        return view('livewire.ad-copy-generator', [
            'remaining' => auth()->user()->remainingQuota(),
            'quota'     => auth()->user()->dailyQuota(),
            'hasKey'    => (bool) auth()->user()->apiKeyFor('openai'),
        ]);
    }
}
