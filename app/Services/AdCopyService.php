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
- Messaging template (in {language}): the ACTUAL warm Messenger auto-reply written as ONE
  plain-text string — NOT a JSON object, NOT key-value pairs. Do NOT wrap it in { } and do NOT
  use keys like "greeting", "benefits", or "cta". Just write the message itself, structured as:
  a friendly greeting line, then EXACTLY 3 benefit lines each starting with "✅ " on its own
  separate line, then a closing call-to-action line. Separate every line with a real line break.
- Quick replies (in {language}): exactly 3 short button labels. Each must be DIFFERENT and unique
  (no repeats/duplicates), each under 25 characters.
- Do NOT include any specific PRICE or PROMO amount in the variants (headline, primary text,
  messaging template, quick replies). Keep them benefit- and hook-focused only. The price and
  promo belong ONLY in the separate main_flow message.
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
3. PRICING + OFFER (ONE line only) — first show the OLD/regular price with a REAL crossed-out line
   using unicode combining strikethrough characters so it literally looks like this: ₱̶3̶6̶0̶ (each
   digit has a line through it). Then, on the NEXT line, show the promo price AND the promo/offer
   deal TOGETHER on ONE single line, emphasized in CAPS with emojis
   (e.g. "👉 ₱240 NA LANG — BUY 1 TAKE 1! 🎉"). Mention the price ONCE only — do NOT repeat the
   price on a separate line, and do NOT restate the word "PROMO". If no distinct offer/deal is given,
   just show the promo price alone on that line. If no old price is given, invent a believable higher
   one (about 1.5x the promo price).
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

    /** Default instruction for the follow-up SEQUENCE messages. Admin-editable. {language} replaced at runtime. */
    public const DEFAULT_SEQUENCE_PROMPT = <<<'SEQ'
You are a top Filipino direct-response copywriter writing a Facebook Messenger FOLLOW-UP SEQUENCE
(BotCake broadcast). Write {count} messages sent one at a time over the next hours/days to re-engage
a customer who messaged but has NOT yet completed their order, pushing them to finally buy.

LANGUAGE: {language} (Taglish by default) — punchy, kabog, conversational, parang totoong seller na
hinahabol ang warm lead. Do NOT output pure English unless {language} is exactly "English".

VARY THE PERSUASION ANGLE — every message must use a DIFFERENT framework. Rotate across these
(don't reuse the same one twice, don't go in a predictable order):
- SCARCITY / urgency (limited stock, last slots, "hanggang ngayon lang", last day)
- CURIOSITY (open loop, teaser, "may sorpresa ako sayo", tanong na hindi agad sinasagot)
- PROBLEM–AGITATE–SOLVE (kilalanin ang sakit/pain point, palakihin, saka i-offer ang solusyon)
- SOCIAL PROOF (dami nang umorder, ubos-ubos, "grabe ang orders today")
- FOMO / gentle guilt ("sayang naman", "iba na kukuha ng slot mo")
- EXCLUSIVITY ("ikaw ang napili", priority, VIP list)
- VALUE / price-anchor ("sa mall x3 presyo", "parang walang tubo", free shipping)
- REASSURANCE / objection-handling (COD, legit, easy return — para mawala ang duda)

VARY THE LENGTH — this is important, huwag pare-pareho:
- Some messages must be SHORT: 1 punchy hook line + a quick order call (parang teaser).
- Some must be MEDIUM: a hook + 2-3 lines of substance.
- Some must be LONGER: a strong hook, then 3-5 lines (mini-story, benefits list na may emoji bullets,
  price anchor, o objection-handling), then a warm order call.
- Mix them up randomly across the sequence — magkakaibang haba, magkakaibang laman.

CALL TO ACTION: end most messages with a natural order/reply prompt in {language}
(e.g. "Order na po? Reply lang! 😊", "Send niyo na po pangalan, number, at address 📩",
"G na po ba? 🛒"). Vary the wording — HUWAG gumamit ng anumang placeholder o token para dito.

FORMATTING:
- This is pasted into Messenger which does NOT render markdown. Never use **asterisks**, ~tildes~, or backticks.
  For emphasis use CAPS, BOLD unicode letters, or emojis. Emojis dapat natural, hindi sa bawat linya.
- Use real line breaks inside a message. No fake medical/financial claims — honest pa rin.

PLACEHOLDERS — insert these LITERALLY, do NOT replace, translate, or invent values for them.
These are the ONLY two allowed placeholders — do NOT output any other {{...}} token:
- {{first_name}}  = the customer's first name (sprinkle naturally, not in every single message)
- {{PRICING}}     = the promo price / offer

Return ONLY the messages as an array of strings (one full message per array item).
Do NOT number them and do NOT add any commentary.
SEQ;

    /**
     * Generate N follow-up SEQUENCE messages (BotCake broadcast style). Returns an array of message strings.
     *
     * @return array<int, string>
     */
    public function generateSequence(User $user, array $input, int $count): array
    {
        $keyRow = $user->apiKeyFor('openai');
        if (! $keyRow) {
            throw new RuntimeException('You have no OpenAI API key yet. Set one up in Settings first.');
        }

        $count    = max(1, min(10, $count));
        $language = $input['language'] ?? 'Taglish';
        $model    = $input['model'] ?? 'gpt-4o';

        $template = trim($input['sequence_prompt'] ?? '') ?: self::DEFAULT_SEQUENCE_PROMPT;
        $system = str_replace(['{count}', '{language}'], [(string) $count, $language], $template);

        $userMsg = "Product name: {$input['product_name']}\n"
            ."Product description: {$input['product_description']}\n"
            ."Key features:\n".trim($input['features'] ?? '')."\n"
            ."Promo price: ".trim($input['price'] ?? '')."\n"
            ."Current promo/offer: ".trim($input['promo'] ?? '')."\n\n"
            ."Write exactly {$count} follow-up messages.";

        $schema = [
            'type'                 => 'object',
            'additionalProperties' => false,
            'required'             => ['messages'],
            'properties'           => [
                'messages' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
        ];

        $client = \OpenAI::client($keyRow->plainKey());
        $response = $client->chat()->create([
            'model'           => $model,
            'temperature'     => 0.95,
            'messages'        => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $userMsg],
            ],
            'response_format' => ['type' => 'json_schema', 'json_schema' => ['name' => 'followup_sequence', 'strict' => true, 'schema' => $schema]],
        ]);

        $keyRow->forceFill(['last_used_at' => now(), 'is_valid' => true])->save();

        $parsed = json_decode($response->choices[0]->message->content ?? '', true);
        $messages = array_values(array_filter(array_map(function ($m) {
            $m = (string) $m;
            // Defensive: strip any {{FFORM}} token (internal placeholder) and clean up blank lines it leaves.
            $m = preg_replace('/\h*\{\{\s*FFORM\s*\}\}\h*/i', '', $m);
            $m = preg_replace("/\n{3,}/", "\n\n", $m);

            return trim($m);
        }, $parsed['messages'] ?? []), fn ($m) => $m !== ''));

        return array_slice($messages, 0, $count);
    }

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
            'variants'         => $this->normalizeVariants(array_values($parsed['variants'])),
            'product_features' => $parsed['product_features'] ?? '',
            'main_flow'        => $parsed['main_flow'] ?? '',
            'model'            => $model,
            'input_tokens'     => $response->usage->promptTokens ?? 0,
            'output_tokens'    => $response->usage->completionTokens ?? 0,
        ];
    }

    /** Regenerate ONLY the ad-copy variants (per-part refresh). */
    public function generateVariants(User $user, array $input): array
    {
        $keyRow = $user->apiKeyFor('openai');
        if (! $keyRow) {
            throw new RuntimeException('You have no OpenAI API key yet. Set one up in Settings first.');
        }

        $count      = (int) ($input['variants'] ?? 1);
        $language   = $input['language'] ?? 'Taglish';
        $tone       = $input['tone'] ?? 'Friendly at persuasive';
        $audience   = trim($input['audience'] ?? '');
        $creativity = (float) ($input['creativity'] ?? 0.7);
        $model      = $input['model'] ?? 'gpt-4o';

        $template = trim($input['system_prompt'] ?? '') ?: self::DEFAULT_SYSTEM;
        $system = str_replace('{language}', $language, $template);

        $userPrompt = "Create {$count} completely different Facebook ad copy variants for this product.\n\n"
            ."Product name: {$input['product_name']}\n"
            ."Product description: {$input['product_description']}\n"
            ."Target audience: {$audience}\n"
            ."Tone: {$tone}";

        $schema = [
            'type'                 => 'object',
            'additionalProperties' => false,
            'required'             => ['variants'],
            'properties'           => [
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
                            'quick_replies'      => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                    ],
                ],
            ],
        ];

        $client = \OpenAI::client($keyRow->plainKey());
        $response = $client->chat()->create([
            'model'           => $model,
            'temperature'     => $creativity,
            'messages'        => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'response_format' => ['type' => 'json_schema', 'json_schema' => ['name' => 'ad_variants', 'strict' => true, 'schema' => $schema]],
        ]);

        $keyRow->forceFill(['last_used_at' => now(), 'is_valid' => true])->save();
        $parsed = json_decode($response->choices[0]->message->content ?? '', true);

        return $this->normalizeVariants(array_values($parsed['variants'] ?? []));
    }

    /** Clean up AI variants: fix JSON-in-messaging_template and de-duplicate quick replies. */
    private function normalizeVariants(array $variants): array
    {
        foreach ($variants as &$v) {
            $mt = $v['messaging_template'] ?? '';
            if (is_string($mt) && str_starts_with(trim($mt), '{')) {
                $decoded = json_decode($mt, true);
                if (is_array($decoded)) {
                    $v['messaging_template'] = trim(implode("\n", array_map(
                        fn ($x) => is_array($x) ? implode("\n", $x) : (string) $x,
                        $decoded
                    )));
                }
            }

            if (! empty($v['quick_replies']) && is_array($v['quick_replies'])) {
                $seen = [];
                $unique = [];
                foreach ($v['quick_replies'] as $qr) {
                    $key = mb_strtolower(trim((string) $qr));
                    if ($key !== '' && ! isset($seen[$key])) {
                        $seen[$key] = true;
                        $unique[] = trim((string) $qr);
                    }
                }
                $v['quick_replies'] = $unique;
            }
        }

        return $variants;
    }

    /** Regenerate ONLY the Main Flow opening message (per-part refresh). */
    public function generateMainFlowOnly(User $user, array $input): string
    {
        $keyRow = $user->apiKeyFor('openai');
        if (! $keyRow) {
            throw new RuntimeException('You have no OpenAI API key yet. Set one up in Settings first.');
        }

        $language = $input['language'] ?? 'Taglish';
        $model    = $input['model'] ?? 'gpt-4o';
        $mainflowPrompt = trim($input['mainflow_prompt'] ?? '') ?: self::DEFAULT_MAINFLOW_PROMPT;
        $system = str_replace('{language}', $language, $mainflowPrompt);

        $userMsg = "Product name: {$input['product_name']}\n"
            ."Product description: {$input['product_description']}\n"
            ."Key features:\n".trim($input['features'] ?? '')."\n"
            ."Promo price: ".trim($input['price'] ?? '')."\n"
            ."Current promo/offer: ".trim($input['promo'] ?? '');

        $client = \OpenAI::client($keyRow->plainKey());
        $response = $client->chat()->create([
            'model'       => $model,
            'temperature' => 0.85,
            'messages'    => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $userMsg],
            ],
        ]);

        $keyRow->forceFill(['last_used_at' => now(), 'is_valid' => true])->save();

        return trim($response->choices[0]->message->content ?? '');
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

    /**
     * Analyze a product image (data URL) and extract product name, description, and key features.
     *
     * @return array{product_name: string, product_description: string, product_features: string}
     */
    public function analyzeProductImage(User $user, string $imageDataUrl, string $model = 'gpt-4o'): array
    {
        $keyRow = $user->apiKeyFor('openai');
        if (! $keyRow) {
            throw new RuntimeException('You have no OpenAI API key yet. Set one up in Settings first.');
        }

        $client = \OpenAI::client($keyRow->plainKey());
        $response = $client->chat()->create([
            'model'           => $model,
            'response_format' => [
                'type'        => 'json_schema',
                'json_schema' => [
                    'name'   => 'product_from_image',
                    'strict' => true,
                    'schema' => [
                        'type'                 => 'object',
                        'additionalProperties' => false,
                        'required'             => ['product_name', 'product_description', 'product_features'],
                        'properties'           => [
                            'product_name'        => ['type' => 'string'],
                            'product_description' => ['type' => 'string'],
                            'product_features'    => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'messages' => [[
                'role'    => 'user',
                'content' => [
                    ['type' => 'text', 'text' => 'Look at this product image. Return: (1) product_name, (2) product_description — a short appealing marketing description (2-4 sentences) for a Facebook ad, and (3) product_features — 4-6 key features/benefits, each on its own line starting with "✅ ". Do not invent specs that are not visible or implied.'],
                    ['type' => 'image_url', 'image_url' => ['url' => $imageDataUrl]],
                ],
            ]],
        ]);

        $keyRow->forceFill(['last_used_at' => now(), 'is_valid' => true])->save();

        $parsed = json_decode($response->choices[0]->message->content ?? '', true);

        return [
            'product_name'        => $parsed['product_name'] ?? '',
            'product_description' => $parsed['product_description'] ?? '',
            'product_features'    => $parsed['product_features'] ?? '',
        ];
    }
}
