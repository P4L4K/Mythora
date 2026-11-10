<?php
/**
 * MYTHORA - ElevenLabs Text-to-Speech Service
 * Connects to ElevenLabs API using backend credentials to generate studio-grade narration
 */

namespace App\Services;

use App\Config\Config;

class TTSService {
    private string $apiKey;
    private string $defaultVoiceId;

    public function __construct() {
        $this->apiKey = Config::get('ELEVENLABS_API_KEY', '');
        $this->defaultVoiceId = Config::get('ELEVENLABS_DEFAULT_VOICE_ID', '21m00Tcm4TlvDq8ikWAM');
    }

    /**
     * Synthesizes text into MP3 audio stream using ElevenLabs API
     */
    public function synthesizeSpeech(string $text, ?string $voiceId = null): ?string {
        $voice = $voiceId ?: $this->defaultVoiceId;

        if (empty($this->apiKey) || $this->apiKey === 'your-elevenlabs-api-key-here') {
            // Development fallback: return null so renderer uses fallback sine audio
            return null;
        }

        $url = "https://api.elevenlabs.io/v1/text-to-speech/{$voice}";

        $postData = [
            'text' => $text,
            'model_id' => 'eleven_multilingual_v2',
            'voice_settings' => [
                'stability' => 0.5,
                'similarity_boost' => 0.75
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "xi-api-key: {$this->apiKey}",
                'Accept: audio/mpeg'
            ],
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_TIMEOUT => 30
        ]);

        $audioData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && !empty($audioData)) {
            return $audioData;
        }

        return null;
    }
}
