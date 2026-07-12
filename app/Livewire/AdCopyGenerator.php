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
        $this->applyDefaults();
    }

    /** Apply system defaults (COD, delivery) + the user's saved defaults to ALL inputs. */
    private function applyDefaults(): void
    {
        $sp = array_fill_keys(array_keys(\App\Services\SalesPromptService::FIELDS), '');
        $sp = array_merge($sp, \App\Services\SalesPromptService::DEFAULTS);

        $saved = auth()->user()->sp_defaults;
        if (is_array($saved)) {
            if (isset($saved['sp']) && is_array($saved['sp'])) {
                $sp = array_merge($sp, $saved['sp']);
            }
            $this->product_name        = $saved['product_name'] ?? $this->product_name;
            $this->product_description = $saved['product_description'] ?? $this->product_description;
            $this->audience            = $saved['audience'] ?? $this->audience;
            $this->language            = $saved['language'] ?? $this->language;
            $this->tone                = $saved['tone'] ?? $this->tone;
            $this->creativity          = $saved['creativity'] ?? $this->creativity;
            $this->variants            = $saved['variants'] ?? $this->variants;
        }

        $this->sp = $sp;
    }

    /** Save ALL current inputs as this user's defaults. */
    public function saveDefaults(): void
    {
        auth()->user()->update(['sp_defaults' => [
            'product_name'        => $this->product_name,
            'product_description' => $this->product_description,
            'audience'            => $this->audience,
            'language'            => $this->language,
            'tone'                => $this->tone,
            'creativity'          => $this->creativity,
            'variants'            => $this->variants,
            'sp'                  => $this->sp,
        ]]);
        session()->flash('sp-msg', 'Saved all inputs as your defaults.');
    }

    /** Reset all inputs back to your saved defaults (or the system defaults). */
    public function resetDefaults(): void
    {
        $this->applyDefaults();
        session()->flash('sp-msg', 'Reset to your defaults.');
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
                'features_prompt'     => $tool->config['features_prompt'] ?? null,
            ]);

            $this->results = $out['variants'];

            // AI-generated Key Features fill the field if the user left it blank (still editable).
            if (trim($this->sp['PRODUCT_FEATURES'] ?? '') === '') {
                $this->sp['PRODUCT_FEATURES'] = $out['product_features'] ?? '';
            }

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

    /** Dedicated button: generate ONLY the Key Features with AI and fill the field. */
    public function generateFeatures(AdCopyService $service): void
    {
        $this->validate([
            'product_name'        => ['required', 'string', 'max:200'],
            'product_description' => ['required', 'string', 'max:4000'],
        ]);

        $user = auth()->user();
        if (! $user->apiKeyFor('openai')) {
            $this->error = 'You have no OpenAI API key yet. Add one in Settings before generating.';

            return;
        }

        $tool = Tool::where('slug', 'ad-copy-generator')->first();

        try {
            $this->sp['PRODUCT_FEATURES'] = $service->generateFeatures($user, [
                'product_name'        => $this->product_name,
                'product_description' => $this->product_description,
                'model'               => $tool->config['default_model'] ?? 'gpt-4o',
                'features_prompt'     => $tool->config['features_prompt'] ?? null,
            ]);
            $this->error = null;
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
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

        if ($field === 'sales_prompt') {
            $text = $this->generatedPrompt;
        } elseif (str_starts_with($field, 'quick_reply_')) {
            $qi = (int) substr($field, strlen('quick_reply_'));
            $text = $this->results[$index]['quick_replies'][$qi] ?? null;
        } else {
            $text = $this->results[$index][$field] ?? null;
        }

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
