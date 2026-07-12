<?php

namespace App\Services;

use App\Models\User;
use RuntimeException;

class AdCopyService
{
    /** Default system prompt. {language} is replaced at runtime. Admin-editable via the tool config. */
    public const DEFAULT_SYSTEM = <<<'SYS'
You are an expert Filipino direct-response Facebook ads copywriter. You write
Messenger-optimized ad copy that gets high click-through and conversion rates
for the Philippine market. You understand local buying psychology and slang.

LANGUAGE (most important):
Write ALL output — headline, primary text, messaging template, and quick replies —
in {language}, REGARDLESS of the language used in the product details below. Even if
the product name and description are written in English, you MUST still write the ad
copy in {language}. If Taglish, mix natural conversational Tagalog + English. Do NOT
output pure English unless {language} is exactly "English".

Rules:
- Each variant must be DISTINCT in angle (problem-agitate, benefit-led, social
  proof, urgency/scarcity, curiosity).
- Headline (in {language}): max 10 words, scroll-stopping.
- Primary text (in {language}): 1-4 short lines, high CTR, ends with a clear call to action.
- Messaging template (in {language}): a warm, multi-line Messenger auto-reply. Structure it as:
  (1) a friendly greeting line, then (2) EXACTLY 3 benefit/feature lines that each
  begin with "✅ ", each on its own separate line, then (3) a closing call-to-action
  line. Put a real line break between every line (use \n in the JSON string).
- Quick replies (in {language}): exactly 3 short button labels, each under 25 characters.
- No fabricated medical/financial claims. Keep it honest and compliant.
SYS;

    /** Default instruction for generating the product's Key Features. Admin-editable. */
    public const DEFAULT_FEATURES_PROMPT = <<<'FP'
From the product name and description, write a concise list of 4-6 key features/benefits.
Format each on its own line starting with "✅ ". Keep each line short and benefit-focused.
Do not invent specs that aren't implied by the description.
FP;

    /** Default instruction for the "Main Flow" opening auto-reply. Admin-editable. */
    public const DEFAULT_MAINFLOW_PROMPT = <<<'MF'
Write ONE warm, complete, high-converting OPENING message that the sales bot sends as its
FIRST reply to any customer who messages. This is the "main flow" opening. Follow this flow:

1. GREETING first — a friendly, warm rapport line (e.g. "Hi po! 😊 Salamat sa pag-message!").
2. A short attention hook about the offer (e.g. "LIMITED TIME OFFER na po ito! 🔥").
3. PRICING — show the OLD/regular price with a REAL crossed-out line using unicode combining
   strikethrough characters so it literally looks like this: ₱̶3̶6̶0̶ (each digit has a line
   through it). Then, on the NEXT line, EMPHASIZE the promo price boldly in CAPS with emojis
   (e.g. "👉 PROMO PRICE: ₱240 NA LANG! 🎉"). If no old price is given, invent a believable
   higher one (about 1.5x the promo price).
4. One punchy hook line about the biggest benefit.
5. A NUMBERED list of key benefits with emojis (1️⃣, 2️⃣, 3️⃣ ...), each on its own line,
   benefit-driven (ALL CAPS keywords are okay).
6. Close with a strong but warm call to action (e.g. "Gusto niyo po bang mag-order? Reply lang po! 😊").

FORMATTING RULES (very important):
- This is pasted into Facebook Messenger, which does NOT render markdown. NEVER use **asterisks**,
  ~tildes~, backticks, or markdown of any kind. For emphasis use CAPS, emojis, and REAL unicode
  strikethrough (combining long stroke overlay) for the old price — never tildes.
- Use real line breaks. Make it engaging and complete — do NOT make it too short.
- Write in {language}. Output plain text only (no JSON).
MF;

    /**
     * Generate Facebook ad copy variants using the user's own OpenAI key.
     *
     * @return array{variants: array, model: string, input_tokens: int, output_tokens: int}
     */
    public function generate(User $user, array $input): array
    {
        $keyRow = $user->apiKeyFor('openai');
        if (! $keyRow) {
            throw new RuntimeException('You have no OpenAI API key yet. Set one up in Settings first.');
        }

        $count      = (int) ($input['variants'] ?? 5);
        $language   = $input['language'] ?? 'Taglish';
        $tone       = $input['tone'] ?? 'Friendly at persuasive';
        $audience   = trim($input['audience'] ?? '');
        $creativity = (float) ($input['creativity'] ?? 0.7);
        $model      = $input['model'] ?? 'gpt-4o';

        // System prompt is admin-editable (stored on the tool config). Falls back to the
        // built-in default. The {language} placeholder is replaced at runtime.
        $template = trim($input['system_prompt'] ?? '') ?: self::DEFAULT_SYSTEM;
        $system = str_replace('{language}', $language, $template);

        // Key Features generation instruction (admin-editable) — appended to the system prompt.
        $featuresPrompt = trim($input['features_prompt'] ?? '') ?: self::DEFAULT_FEATURES_PROMPT;
        $system .= "\n\n---\n\nAdditionally, produce a \"product_features\" string:\n".$featuresPrompt;

        // Main Flow (opening auto-reply) instruction (admin-editable).
        $mainflowPrompt = trim($input['mainflow_prompt'] ?? '') ?: self::DEFAULT_MAINFLOW_PROMPT;
        $mainflowPrompt = str_replace('{language}', $language, $mainflowPrompt);
        $system .= "\n\n---\n\nAlso produce a \"main_flow\" string:\n".$mainflowPrompt;

        $price = trim($input['price'] ?? '');
        $promo = trim($input['promo'] ?? '');

        $userPrompt = <<<TXT
Create {$count} completely different Facebook ad copy variants for this product.

Product name: {$input['product_name']}
Product description: {$input['product_description']}
Target audience: {$audience}
Tone: {$tone}
Promo price: {$price}
Current promo/offer: {$promo}
TXT;

        $schema = [
            'type'                 => 'object',
            'additionalProperties' => false,
            'required'             => ['variants', 'product_features', 'main_flow'],
            'properties'           => [
                'product_features' => ['type' => 'string'],
                'main_flow'        => ['type' => 'string'],
                'variants' => [
                    'type'  => 'array',
                    'items' => [
                        'type'                 => 'object',
                        'additionalProperties' => false,
                        'required'             => ['angle', 'headline', 'primary_text', 'messaging_template', 'quick_replies'],
                        'properties'           => [
                            'angle'              => ['type' => 'string'],
                            'headline'           => ['type' => 'string'],
                            'primary_text'       => ['type' => 'string'],
                            'messaging_template' => ['type' => 'string'],
                            'quick_replies'      => [
                                'type'  => 'array',
                                'items' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $client = \OpenAI::client($keyRow->plainKey());

        $response = $client->chat()->create([
            'model'    => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user',   'content' => $userPrompt],
            ],
            'temperature'     => $creativity,
            'response_format' => [
                'type'        => 'json_schema',
                'json_schema' => [
                    'name'   => 'ad_variants',
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],
        ]);

        $content = $response->choices[0]->message->content ?? '';
        $parsed  = json_decode($content, true);

        if (! is_array($parsed) || ! isset($parsed['variants'])) {
            throw new RuntimeException("Couldn't parse the AI response. Please try again.");
        }

        // Mark the key as used and valid.
        $keyRow->forceFill(['last_used_at' => now(), 'is_valid' => true])->save();

        return [
            'variants'         => array_values($parsed['variants']),
            'product_features' => $parsed['product_features'] ?? '',
            'main_flow'        => $parsed['main_flow'] ?? '',
            'model'            => $model,
            'input_tokens'     => $response->usage->promptTokens ?? 0,
            'output_tokens'    => $response->usage->completionTokens ?? 0,
        ];
    }

    /** Generate ONLY the product's Key Features (used by the dedicated button). */
    public function generateFeatures(User $user, array $input): string
    {
        $keyRow = $user->apiKeyFor('openai');
        if (! $keyRow) {
            throw new RuntimeException('You have no OpenAI API key yet. Set one up in Settings first.');
        }

        $featuresPrompt = trim($input['features_prompt'] ?? '') ?: self::DEFAULT_FEATURES_PROMPT;
        $model = $input['model'] ?? 'gpt-4o';

        $client = \OpenAI::client($keyRow->plainKey());
        $response = $client->chat()->create([
            'model'       => $model,
            'temperature' => 0.6,
            'messages'    => [
                ['role' => 'system', 'content' => $featuresPrompt],
                ['role' => 'user', 'content' => "Product name: {$input['product_name']}\nProduct description: {$input['product_description']}"],
            ],
        ]);

        $keyRow->forceFill(['last_used_at' => now(), 'is_valid' => true])->save();

        return trim($response->choices[0]->message->content ?? '');
    }

    /** Test a prompt: run a single customer message against the given system prompt. */
    public function testChat(User $user, string $systemPrompt, string $message, string $model = 'gpt-4o'): string
    {
        $keyRow = $user->apiKeyFor('openai');
        if (! $keyRow) {
            throw new RuntimeException('You have no OpenAI API key yet. Set one up in Settings first.');
        }

        $client = \OpenAI::client($keyRow->plainKey());
        $response = $client->chat()->create([
            'model'       => $model,
            'temperature' => 0.7,
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message],
            ],
        ]);

        $keyRow->forceFill(['last_used_at' => now(), 'is_valid' => true])->save();

        return trim($response->choices[0]->message->content ?? '');
    }

    /** Generate a promo image with DALL·E 3. Returns raw PNG bytes. */
    public function generateImage(User $user, string $prompt): string
    {
        $keyRow = $user->apiKeyFor('openai');
        if (! $keyRow) {
            throw new RuntimeException('You have no OpenAI API key yet. Set one up in Settings first.');
        }

        $client = \OpenAI::client($keyRow->plainKey());
        $response = $client->images()->create([
            'model'           => 'dall-e-3',
            'prompt'          => $prompt,
            'n'               => 1,
            'size'            => '1024x1024',
            'response_format' => 'b64_json',
        ]);

        $keyRow->forceFill(['last_used_at' => now(), 'is_valid' => true])->save();

        $b64 = $response->data[0]->b64_json ?? '';

        return $b64 ? base64_decode($b64) : '';
    }
}
