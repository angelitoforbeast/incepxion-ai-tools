<?php

namespace App\Livewire;

use App\Models\Generation;
use App\Models\Tool;
use App\Services\AdCopyService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Ad Copy Generator')]
class AdCopyGenerator extends Component
{
    use WithFileUploads;

    /** Optional product image the seller uploads (for auto-fill + reference). */
    public $uploadedImage = null;
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
    public ?string $mainFlow = null;
    public ?string $generatedPrompt = null;
    public ?string $generatedAfterSalesPrompt = null;

    /** Follow-up SEQUENCE (BotCake broadcast messages). */
    #[Validate('required|integer|min:1|max:10')]
    public int $sequenceCount = 10;
    public array $sequenceMessages = [];

    // Prompt playground (test a customer message against the generated prompt)
    #[Validate('nullable|string|max:2000')]
    public string $salesTestInput = '';
    public ?string $salesTestReply = null;
    #[Validate('nullable|string|max:2000')]
    public string $afterSalesTestInput = '';
    public ?string $afterSalesTestReply = null;

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

    /** Length caps for every persisted text field (guards the stored JSON row size). */
    private function lengthRules(): array
    {
        $rules = [
            'product_name'        => ['nullable', 'string', 'max:200'],
            'product_description' => ['nullable', 'string', 'max:4000'],
            'audience'            => ['nullable', 'string', 'max:500'],
            'tone'                => ['nullable', 'string', 'max:60'],
        ];
        foreach (array_keys(\App\Services\SalesPromptService::FIELDS) as $key) {
            $rules['sp.'.$key] = ['nullable', 'string', 'max:'.(\App\Services\SalesPromptService::MAX[$key] ?? 1000)];
        }

        return $rules;
    }

    /** Save ALL current inputs as this user's defaults. */
    public function saveDefaults(): void
    {
        // Cap every field so a bypassed client can't store an oversized defaults blob.
        $this->validate($this->lengthRules());

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

        // Sales-prompt placeholder fields: required ones must be filled, and EVERY field is
        // length-capped so the stored generation row can't balloon (see SalesPromptService::MAX).
        $rules = [];
        $messages = [];
        foreach (array_keys(\App\Services\SalesPromptService::FIELDS) as $key) {
            $max = \App\Services\SalesPromptService::MAX[$key] ?? 1000;
            $required = in_array($key, \App\Services\SalesPromptService::REQUIRED, true);
            $rules['sp.'.$key] = [$required ? 'required' : 'nullable', 'string', 'max:'.$max];
            $messages['sp.'.$key.'.required'] = \App\Services\SalesPromptService::FIELDS[$key].' is required.';
            $messages['sp.'.$key.'.max'] = \App\Services\SalesPromptService::FIELDS[$key].' is too long (max '.number_format($max).' characters).';
        }
        $this->validate($rules, $messages);

        $this->error = null;
        $this->results = [];

        $user = auth()->user();

        if (! $user->apiKeyFor('openai')) {
            $this->error = 'You have no OpenAI API key yet. Add one in Settings before generating.';

            return;
        }

        // Daily quota enforcement is disabled — generation is unlimited (users bring their
        // own OpenAI key, so there's no cost to us). Usage is still recorded silently.

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
                'mainflow_prompt'     => $tool->config['mainflow_prompt'] ?? null,
                'price'               => $this->sp['PRODUCT_PRICE'] ?? '',
                'promo'               => $this->sp['PROMO'] ?? '',
            ]);

            $this->results = $out['variants'];
            $this->mainFlow = $out['main_flow'] ?? null;

            // AI-generated Key Features fill the field if the user left it blank (still editable).
            if (trim($this->sp['PRODUCT_FEATURES'] ?? '') === '') {
                $this->sp['PRODUCT_FEATURES'] = $out['product_features'] ?? '';
            }

            // Fill the BotCake sales-assistant prompt from the placeholder inputs.
            $values = $this->sp;
            $values['PRODUCT_NAME'] = ($values['PRODUCT_NAME'] ?? '') ?: $this->product_name;
            $values['PRODUCT_INFORMATION'] = ($values['PRODUCT_INFORMATION'] ?? '') ?: $this->product_description;
            $svc = app(\App\Services\SalesPromptService::class);
            $template = $tool->config['botcake_template'] ?? \App\Services\SalesPromptService::DEFAULT_TEMPLATE;
            $this->generatedPrompt = $svc->fill($template, $values);
            $aftersalesTemplate = $tool->config['aftersales_template'] ?? \App\Services\SalesPromptService::DEFAULT_AFTERSALES_TEMPLATE;
            $this->generatedAfterSalesPrompt = $svc->fill($aftersalesTemplate, $values);

            // Best-effort logging + usage tracking. The ad copy is already produced and
            // shown above, so a DB failure here (e.g. disk full) must NEVER hide the result
            // or surface an error to the user.
            try {
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
                    'output'        => ['main_flow' => $this->mainFlow, 'variants' => $out['variants'], 'sales_prompt' => $this->generatedPrompt, 'aftersales_prompt' => $this->generatedAfterSalesPrompt],
                    'input_tokens'  => $out['input_tokens'],
                    'output_tokens' => $out['output_tokens'],
                    'status'        => 'success',
                    'duration_ms'   => (int) ((microtime(true) - $start) * 1000),
                ]);

                $this->lastGenerationId = $generation->id;
                $user->recordUsage();
            } catch (\Throwable $logError) {
                Log::warning('Ad copy produced but logging/usage failed', ['user' => $user->id, 'msg' => $logError->getMessage()]);
            }
        } catch (\Throwable $e) {
            // Only reached when the generation itself failed (OpenAI/API error) — here an
            // error IS appropriate because there is no result to show.
            $this->error = $e->getMessage();
            Log::error('Ad copy generation failed', ['user' => $user->id, 'msg' => $e->getMessage()]);

            try {
                Generation::create([
                    'user_id'     => $user->id,
                    'tool_id'     => $tool?->id,
                    'provider'    => 'openai',
                    'input'       => ['product_name' => $this->product_name],
                    'status'      => 'error',
                    'error'       => mb_substr($e->getMessage(), 0, 1000),
                    'duration_ms' => (int) ((microtime(true) - $start) * 1000),
                ]);
            } catch (\Throwable $ignored) {
                // Logging the failure itself failed (e.g. disk full) — nothing else to do.
            }
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

    /** Refresh ONLY the Ad Creatives (variants). */
    public function regenerateAdCreatives(AdCopyService $service): void
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
            $this->results = $service->generateVariants($user, [
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
            $this->error = null;
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    /** Refresh ONLY the Main Flow message. */
    public function regenerateMainFlow(AdCopyService $service): void
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
            $this->mainFlow = $service->generateMainFlowOnly($user, [
                'product_name'        => $this->product_name,
                'product_description' => $this->product_description,
                'features'            => $this->sp['PRODUCT_FEATURES'] ?? '',
                'price'               => $this->sp['PRODUCT_PRICE'] ?? '',
                'promo'               => $this->sp['PROMO'] ?? '',
                'language'            => $this->language,
                'model'               => $tool->config['default_model'] ?? 'gpt-4o',
                'mainflow_prompt'     => $tool->config['mainflow_prompt'] ?? null,
            ]);
            $this->error = null;
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    /** Generate the follow-up SEQUENCE (N broadcast messages) for the customer. */
    public function generateSequence(AdCopyService $service): void
    {
        $this->validate([
            'product_name'        => ['required', 'string', 'max:200'],
            'product_description' => ['required', 'string', 'max:4000'],
            'sequenceCount'       => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $user = auth()->user();
        if (! $user->apiKeyFor('openai')) {
            $this->error = 'You have no OpenAI API key yet. Add one in Settings before generating.';

            return;
        }

        $tool = Tool::where('slug', 'ad-copy-generator')->first();

        try {
            $this->sequenceMessages = $service->generateSequence($user, [
                'product_name'        => $this->product_name,
                'product_description' => $this->product_description,
                'features'            => $this->sp['PRODUCT_FEATURES'] ?? '',
                'price'               => $this->sp['PRODUCT_PRICE'] ?? '',
                'promo'               => $this->sp['PROMO'] ?? '',
                'language'            => $this->language,
                'model'               => $tool->config['default_model'] ?? 'gpt-4o',
                'sequence_prompt'     => $tool->config['sequence_prompt'] ?? null,
            ], $this->sequenceCount);
            $this->error = null;
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    /** Auto-fill Product Name + Description from an uploaded product image (GPT-4o vision). */
    public function autoFillFromImage(AdCopyService $service): void
    {
        $this->validate([
            'uploadedImage' => ['required', 'image', 'max:8192'], // 8MB
        ], [
            'uploadedImage.required' => 'Please upload a product image first.',
        ]);

        $user = auth()->user();
        if (! $user->apiKeyFor('openai')) {
            $this->error = 'You have no OpenAI API key yet. Add one in Settings before generating.';

            return;
        }

        $tool = Tool::where('slug', 'ad-copy-generator')->first();

        try {
            $bytes = file_get_contents($this->uploadedImage->getRealPath());
            $dataUrl = 'data:'.$this->uploadedImage->getMimeType().';base64,'.base64_encode($bytes);

            $result = $service->analyzeProductImage($user, $dataUrl, $tool->config['default_model'] ?? 'gpt-4o');

            if (! empty($result['product_name'])) {
                $this->product_name = $result['product_name'];
            }
            if (! empty($result['product_description'])) {
                $this->product_description = $result['product_description'];
            }
            if (! empty($result['product_features'])) {
                $this->sp['PRODUCT_FEATURES'] = $result['product_features'];
            }
            $this->error = null;
        } catch (\Throwable $e) {
            $this->error = 'Image analysis error: '.$e->getMessage();
        }
    }

    public function removeUpload(): void
    {
        $this->reset('uploadedImage');
    }

    public function testSales(AdCopyService $service): void
    {
        $this->runTest($service, 'sales');
    }

    public function testAfterSales(AdCopyService $service): void
    {
        $this->runTest($service, 'aftersales');
    }

    /** Run one customer message against the generated prompt and show the AI reply. */
    private function runTest(AdCopyService $service, string $type): void
    {
        $this->validateOnly($type === 'aftersales' ? 'afterSalesTestInput' : 'salesTestInput');

        $prompt = $type === 'aftersales' ? $this->generatedAfterSalesPrompt : $this->generatedPrompt;
        $message = trim($type === 'aftersales' ? $this->afterSalesTestInput : $this->salesTestInput);

        if (! $prompt || $message === '') {
            return;
        }

        $user = auth()->user();
        $tool = Tool::where('slug', 'ad-copy-generator')->first();

        try {
            $reply = $service->testChat($user, $prompt, $message, $tool->config['default_model'] ?? 'gpt-4o');
        } catch (\Throwable $e) {
            $reply = '⚠️ '.$e->getMessage();
        }

        if ($type === 'aftersales') {
            $this->afterSalesTestReply = $reply;
        } else {
            $this->salesTestReply = $reply;
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

        if ($field === 'main_flow') {
            $text = $this->mainFlow;
        } elseif ($field === 'sales_prompt') {
            $text = $this->generatedPrompt;
        } elseif ($field === 'aftersales_prompt') {
            $text = $this->generatedAfterSalesPrompt;
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
