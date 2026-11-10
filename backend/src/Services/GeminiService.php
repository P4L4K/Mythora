<?php
/**
 * MYTHORA - Google Gemini AI Service
 * Robust, quota-aware script & scene generator
 * Automatically extracts article content when URLs are provided
 */

namespace App\Services;

use App\Config\Config;

class GeminiService {
    private string $apiKey;
    private CacheService $cache;

    public function __construct() {
        $this->apiKey = Config::get('GEMINI_API_KEY', '');
        $this->cache = new CacheService();
    }

    /**
     * Feature B: Hook Lab
     * Generates 3 opening psychological hooks: Curiosity, Story, Educational
     */
    public function generateHooks(string $prompt, string $platform = 'tiktok'): array {
        $cacheKey = "hooks:" . md5($prompt . ':' . $platform);
        $cached = $this->cache->get($cacheKey);
        if ($cached) {
            return $cached;
        }

        if (empty($this->apiKey) || $this->apiKey === 'your-gemini-api-key-here') {
            return $this->getMockHooks($prompt);
        }

        $systemInstruction = "You are an elite short-form video copywriter. Generate 3 distinct opening hooks for {$platform}: 1) Curiosity-Driven 2) Story-Driven 3) Educational/Provocative. Output strictly a JSON array of objects with keys 'type', 'angle', and 'text'.";

        $result = $this->callGemini("Topic/Content: {$prompt}\n{$systemInstruction}");

        if ($result && is_array($result)) {
            $this->cache->set($cacheKey, $result, 86400 * 3);
            return $result;
        }

        return $this->getMockHooks($prompt);
    }

    /**
     * Generates structured script, scene breakdown, captions, and hashtags
     * Communicates the actual topic of the source text/article
     */
    public function generateScriptAndScenes(array $params): array {
        $inputType = $params['input_type'] ?? 'prompt';
        $rawSource = $params['source_content'] ?? '';
        $hook = $params['selected_hook'] ?? '';
        $targetDuration = $params['target_duration_sec'] ?? 30;
        $style = $params['visual_style'] ?? 'Cinematic';
        $platform = $params['target_platform'] ?? 'tiktok';

        // 1. If URL input, extract actual article content
        $extractedContent = $rawSource;
        if ($inputType === 'url' || filter_var($rawSource, FILTER_VALIDATE_URL)) {
            $articleText = ArticleExtractorService::extractContent($rawSource);
            if (!empty($articleText)) {
                $extractedContent = $articleText;
            }
        }

        $sceneCount = max(3, min(6, intval($targetDuration / 5)));
        $cacheKey = "script:" . md5(json_encode([$extractedContent, $hook, $sceneCount, $style, $platform]));
        $cached = $this->cache->get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $promptInstruction = "You are a professional video director and storyteller. "
            . "Create a structured storyboard of exactly {$sceneCount} sequential scenes for a {$targetDuration}-second vertical short-form video on {$platform}. "
            . "Visual Style: {$style}. Opening Hook: '{$hook}'. "
            . "SOURCE TOPIC / ARTICLE CONTENT:\n{$extractedContent}\n\n"
            . "Requirements:\n"
            . "- DO NOT output URLs or web links in the narration or subtitles.\n"
            . "- The narration must teach, explain, or tell a compelling story about the subject.\n"
            . "- For each scene provide strictly in JSON:\n"
            . "  * scene_order (integer 1 to {$sceneCount})\n"
            . "  * narration_text (complete spoken sentence for voiceover)\n"
            . "  * subtitle_text (short, punchy on-screen caption without links)\n"
            . "  * visual_prompt (descriptive scene setting for photography)\n"
            . "  * estimated_duration (float, e.g. 5.0)\n"
            . "Return strictly a JSON array of scene objects.";

        $result = $this->callGemini($promptInstruction);

        if ($result && is_array($result) && count($result) >= 2) {
            // Assign real contextual images to each scene
            $imgService = new ImageService();
            foreach ($result as &$scene) {
                $scene['media_asset_url'] = $imgService->resolveSceneImage(
                    $scene['visual_prompt'] ?? $scene['narration_text'],
                    $style,
                    (int)($scene['scene_order'] ?? 1)
                );
            }
            $this->cache->set($cacheKey, $result, 86400 * 3);
            return $result;
        }

        return $this->generateStructuredFallbackScenes($extractedContent, $hook, $sceneCount, $style);
    }

    /**
     * Calls Gemini API with model fallback (gemini-2.5-flash / gemini-3.5-flash-lite)
     */
    private function callGemini(string $promptText): ?array {
        $models = ['gemini-2.5-flash', 'gemini-3.5-flash-lite', 'gemini-3.8-flash'];

        foreach ($models as $model) {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->apiKey}";

            $postData = [
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $promptText]]]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json'
                ]
            ];

            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_TIMEOUT => 25,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                $json = json_decode($response, true);
                $rawText = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($rawText) {
                    $parsed = json_decode($rawText, true);
                    if (is_array($parsed)) {
                        return $parsed;
                    }
                }
            }
        }

        return null;
    }

    private function getMockHooks(string $prompt): array {
        $clean = preg_replace('#https?://\S+#i', '', $prompt);
        $topic = !empty(trim($clean)) ? trim($clean) : "freelancing";

        return [
            [
                'type' => 'Curiosity-Driven',
                'angle' => 'Pattern interrupt',
                'text' => "What if everything you were told about full-time jobs was wrong?"
            ],
            [
                'type' => 'Story-Driven',
                'angle' => 'Relatable struggle',
                'text' => "I left my 9-to-5 with zero clients. Here is what happened in month one."
            ],
            [
                'type' => 'Educational / Provocative',
                'angle' => 'Direct insight',
                'text' => "Freelancing isn't just taking random gigs. It's building your own micro-agency."
            ]
        ];
    }

    private function generateStructuredFallbackScenes(string $content, string $hook, int $count, string $style): array {
        $imgService = new ImageService();
        $isFreelance = stripos($content, 'freelanc') !== false || stripos($content, 'upwork') !== false;

        $opening = !empty($hook) ? $hook : ($isFreelance ? "What if you could work from anywhere and choose your own projects?" : "Here is what 99% of people misunderstand about this topic.");

        $storyline = $isFreelance ? [
            1 => ["text" => $opening, "sub" => "Work On Your Own Terms", "prompt" => "Freelancer laptop workspace coffee desk creative freedom"],
            2 => ["text" => "Freelancing means offering your specialized skills as an independent contractor rather than a traditional employee.", "sub" => "Be Your Own Boss", "prompt" => "Independent professional remote work strategy planning"],
            3 => ["text" => "You get to set your own rates, choose who you work with, and scale your income on your own schedule.", "sub" => "Unlimited Income Potential", "prompt" => "Success financial growth desk graph laptop digital work"],
            4 => ["text" => "From design and coding to marketing and writing, the global freelance economy is bigger than ever.", "sub" => "High-Demand Global Skills", "prompt" => "Modern tech developer design workspace creative collaboration"],
            5 => ["text" => "Start by building a focused portfolio, landing your first client, and mastering your craft.", "sub" => "Start Your Journey Today", "prompt" => "Confident professional smiling holding laptop coffee shop"]
        ] : [
            1 => ["text" => $opening, "sub" => "The Secret Revealed", "prompt" => "Cinematic opening concept dramatic lighting"],
            2 => ["text" => "First, you need to understand the fundamental shift happening in the industry today.", "sub" => "The Big Shift", "prompt" => "Dynamic modern strategy concept"],
            3 => ["text" => "Those who adapt early and build real practical skills are the ones who thrive.", "sub" => "Build High-Value Skills", "prompt" => "Focused execution growth mindset"],
            4 => ["text" => "Take action today, stay consistent, and turn your knowledge into real results.", "sub" => "Take Action Now", "prompt" => "Success breakthrough futuristic celebration"]
        ];

        $scenes = [];
        for ($i = 1; $i <= $count; $i++) {
            $item = $storyline[$i] ?? $storyline[1];
            $scenes[] = [
                'scene_order' => $i,
                'narration_text' => $item['text'],
                'visual_prompt' => $item['prompt'],
                'subtitle_text' => $item['sub'],
                'estimated_duration' => 5.0,
                'media_asset_url' => $imgService->resolveSceneImage($item['prompt'], $style, $i)
            ];
        }

        return $scenes;
    }
}
