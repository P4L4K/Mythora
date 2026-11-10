<?php
/**
 * VYRAL STUDIO - AI LLM Service (Google Gemini / OpenAI Architecture)
 * Handles Hook Lab variations, Structured Script Generation, and Scene Segmentation
 */

namespace App\Services;

use App\Config\Config;

class GeminiService {
    private string $apiKey;
    private string $model;

    public function __construct() {
        $this->apiKey = Config::get('GEMINI_API_KEY', '');
        $this->model = Config::get('GEMINI_MODEL', 'gemini-1.5-flash');
    }

    /**
     * Feature B: Hook Lab
     * Generates 3 opening psychological hooks: Curiosity, Story, Educational
     */
    public function generateHooks(string $prompt, string $platform = 'tiktok'): array {
        if (empty($this->apiKey) || $this->apiKey === 'your-gemini-api-key-here') {
            // Intelligent Fallback Generator for local/demo execution
            return [
                [
                    'type' => 'Curiosity-Driven',
                    'angle' => 'Pattern interrupt question',
                    'text' => "Nobody talks about this, but " . rtrim($prompt, '.') . " actually works backwards..."
                ],
                [
                    'type' => 'Story-Driven',
                    'angle' => 'Immediate tension and stakes',
                    'text' => "Last year I thought " . rtrim($prompt, '.') . " was impossible. Then this happened."
                ],
                [
                    'type' => 'Educational / Provocative',
                    'angle' => 'Direct counter-intuitive claim',
                    'text' => "Stop doing " . rtrim($prompt, '.') . " the usual way. Here is what 99% get wrong in 30 seconds."
                ]
            ];
        }

        // Real Gemini REST API Call
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";
        $systemInstruction = "You are an elite short-form video copywriter. Generate 3 distinct hooks for {$platform}: 1) Curiosity-Driven 2) Story-Driven 3) Educational/Provocative. Output strictly JSON array with keys 'type', 'angle', 'text'.";

        $postData = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => "Prompt: {$prompt}\n{$systemInstruction}"]]]
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
            CURLOPT_TIMEOUT => 20
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($response, true);
        $rawText = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if ($rawText) {
            $parsed = json_decode($rawText, true);
            if (is_array($parsed)) return $parsed;
        }

        return $this->generateHooks($prompt, $platform);
    }

    /**
     * Generates structured script and granular scenes breakdown
     */
    public function generateScriptAndScenes(array $params): array {
        $prompt = $params['source_content'] ?? '';
        $hook = $params['selected_hook'] ?? '';
        $targetDuration = $params['target_duration_sec'] ?? 30;
        $style = $params['visual_style'] ?? 'Cinematic';

        // Calculate scene count (~4 to 6 seconds per scene for vertical short form)
        $sceneCount = max(3, min(8, intval($targetDuration / 5)));

        if (empty($this->apiKey) || $this->apiKey === 'your-gemini-api-key-here') {
            return $this->generateStructuredFallbackScenes($prompt, $hook, $sceneCount, $style);
        }

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";
        $instructions = "You are a professional video director. Break down this concept into {$sceneCount} scenes. Each scene must include: scene_order, narration_text, visual_prompt (descriptive prompt for image generation in {$style} style), subtitle_text, estimated_duration (in seconds). Return JSON array of scenes.";

        $postData = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => "Topic: {$prompt}\nOpening Hook: {$hook}\n{$instructions}"]]]
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
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($response, true);
        $rawText = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if ($rawText) {
            $parsed = json_decode($rawText, true);
            if (is_array($parsed)) return $parsed;
        }

        return $this->generateStructuredFallbackScenes($prompt, $hook, $sceneCount, $style);
    }

    private function generateStructuredFallbackScenes(string $prompt, string $hook, int $count, string $style): array {
        $opening = !empty($hook) ? $hook : "Here is something most people never realize about " . rtrim($prompt, '.') . ".";
        
        $stockImages = [
            "https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=1080&q=80",
            "https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1080&q=80",
            "https://images.unsplash.com/photo-1470240731273-7821a6eeb6bd?w=1080&q=80",
            "https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1080&q=80",
            "https://images.unsplash.com/photo-1519681393784-d120267933ba?w=1080&q=80"
        ];

        $scenes = [];
        $scenes[] = [
            'scene_order' => 1,
            'narration_text' => $opening,
            'visual_prompt' => "Dramatic opening shot representing {$prompt}, {$style} aesthetic, cinematic lighting, 8k vertical composition",
            'subtitle_text' => $opening,
            'estimated_duration' => 5.0,
            'media_asset_url' => $stockImages[0]
        ];

        for ($i = 2; $i <= $count; $i++) {
            $text = "Step {$i}: Building momentum with key insight {$i} regarding " . rtrim($prompt, '.') . ".";
            $scenes[] = [
                'scene_order' => $i,
                'narration_text' => $text,
                'visual_prompt' => "Dynamic high quality visualization of {$prompt} phase {$i}, {$style} atmospheric details",
                'subtitle_text' => $text,
                'estimated_duration' => 5.0,
                'media_asset_url' => $stockImages[($i - 1) % count($stockImages)]
            ];
        }

        return $scenes;
    }
}

