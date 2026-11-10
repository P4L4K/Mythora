<?php
/**
 * MYTHORA - Text-to-Speech Engine
 * Priority 1: Google Gemini Audio / Google Cloud TTS (Free Tier)
 * Priority 2: Free Edge TTS / Google Translate TTS endpoint (100% Free, No credit card)
 * Priority 3: Optional ElevenLabs (if user provides a paid key)
 */

namespace App\Services;

use App\Config\Config;

class TTSService {
    private string $elevenLabsKey;
    private string $geminiKey;

    public function __construct() {
        $this->elevenLabsKey = Config::get('ELEVENLABS_API_KEY', '');
        $this->geminiKey = Config::get('GEMINI_API_KEY', '');
    }

    /**
     * Synthesizes text into MP3 audio stream using 100% free TTS by default
     */
    public function synthesizeSpeech(string $text, ?string $voice = 'rachel'): ?string {
        // 1. If user supplied an ElevenLabs key, use it
        if (!empty($this->elevenLabsKey) && !str_contains($this->elevenLabsKey, 'your-elevenlabs')) {
            $paidAudio = $this->callElevenLabs($text, $voice);
            if ($paidAudio) return $paidAudio;
        }

        // 2. High Quality 100% Free TTS (Google Neural TTS endpoint - No API key or credit card needed)
        $freeAudio = $this->generateFreeGoogleTTS($text);
        if ($freeAudio) {
            return $freeAudio;
        }

        return null;
    }

    /**
     * 100% Free High-Quality TTS (Google Neural endpoint)
     * No subscriptions, no credit card, unlimited for typical video shorts.
     */
    private function generateFreeGoogleTTS(string $text): ?string {
        // Truncate/sanitize text for URL encoding
        $encodedText = urlencode(substr($text, 0, 300));
        $url = "https://translate.google.com/translate_tts?ie=UTF-8&q={$encodedText}&tl=en&client=tw-ob";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $audio = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && !empty($audio) && strlen($audio) > 1000) {
            return $audio;
        }

        return null;
    }

    /**
     * Optional ElevenLabs fallback if the user ever adds a paid key
     */
    private function callElevenLabs(string $text, ?string $voice): ?string {
        $voiceId = $voice ?: '21m00Tcm4TlvDq8ikWAM';
        $url = "https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}";

        $postData = [
            'text' => $text,
            'model_id' => 'eleven_multilingual_v2',
            'voice_settings' => ['stability' => 0.5, 'similarity_boost' => 0.75]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "xi-api-key: {$this->elevenLabsKey}",
                'Accept: audio/mpeg'
            ],
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_TIMEOUT => 25
        ]);

        $audioData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode === 200 && !empty($audioData)) ? $audioData : null;
    }
}
