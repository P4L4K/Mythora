<?php
/**
 * MYTHORA - Text-to-Speech Engine
 * Multi-Provider Audio Architecture:
 * 1. Edge-TTS / Free Neural Voices (100% Free, unlimited, zero API key required)
 * 2. Google Translate / Android TTS Fallback (100% Free)
 * 3. ElevenLabs API (Optional premium tier if key provided)
 */

namespace App\Services;

use App\Config\Config;

class TTSService {
    private string $elevenLabsKey;
    private string $defaultVoice;

    public function __construct() {
        $this->elevenLabsKey = Config::get('ELEVENLABS_API_KEY', '');
        $this->defaultVoice = Config::get('ELEVENLABS_DEFAULT_VOICE_ID', '21m00Tcm4TlvDq8ikWAM');
    }

    /**
     * Synthesizes audio narration into an MP3 file
     * Defaults to 100% FREE Microsoft Neural / Google TTS if ElevenLabs key is not set.
     */
    public function generateSpeech(string $text, string $outputMp3Path, string $voice = 'rachel'): bool {
        // If an ElevenLabs API key is supplied, use it
        if (!empty($this->elevenLabsKey) && $this->elevenLabsKey !== 'your-elevenlabs-api-key-here') {
            $audioData = $this->callElevenLabs($text, $voice);
            if ($audioData) {
                file_put_contents($outputMp3Path, $audioData);
                return true;
            }
        }

        // 100% FREE FALLBACK 1: Free Neural Edge-TTS Stream Endpoint
        if ($this->generateFreeNeuralTTS($text, $outputMp3Path, $voice)) {
            return true;
        }

        // 100% FREE FALLBACK 2: Google Speech API (Public web TTS endpoint, zero keys needed)
        if ($this->generateFreeGoogleTTS($text, $outputMp3Path)) {
            return true;
        }

        return false;
    }

    /**
     * Free Microsoft Edge / Neural Speech Web Endpoint (Zero cost, no account)
     */
    private function generateFreeNeuralTTS(string $text, string $outputPath, string $voice): bool {
        // Map friendly names to high quality Microsoft natural voices
        $voiceMap = [
            'rachel' => 'en-US-JennyNeural',
            'antoni' => 'en-US-GuyNeural',
            'bella'  => 'en-US-AriaNeural',
            'adam'   => 'en-US-DavisNeural'
        ];
        $selectedVoice = $voiceMap[$voice] ?? 'en-US-JennyNeural';

        // Microsoft Speech synthesize endpoint
        $encodedText = urlencode(mb_substr($text, 0, 300));
        $url = "https://speech.platform.bing.com/consumer/speech/synthesize/readaloud/edge/v1?trustedclienttoken=6A5AA1D4EAFF4E9FB37E23D68491D6F4";

        $ssml = "<speak version='1.0' xmlns='http://www.w3.org/2001/10/synthesis' xml:lang='en-US'>"
              . "<voice name='{$selectedVoice}'>{$text}</voice></speak>";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/ssml+xml',
                'X-Microsoft-OutputFormat: audio-24khz-48kbitrate-mono-mp3',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
            ],
            CURLOPT_POSTFIELDS => $ssml,
            CURLOPT_TIMEOUT => 15
        ]);

        $audio = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && strlen($audio) > 500) {
            file_put_contents($outputPath, $audio);
            return true;
        }

        return false;
    }

    /**
     * 100% Free Google Public TTS Endpoint (Zero setup, no key needed)
     */
    private function generateFreeGoogleTTS(string $text, string $outputPath): bool {
        // Break down into sentence chunks under 100 chars
        $chunk = substr($text, 0, 150);
        $url = "https://translate.google.com/translate_tts?ie=UTF-8&q=" . urlencode($chunk) . "&tl=en&client=tw-ob";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            CURLOPT_TIMEOUT => 10
        ]);

        $audio = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && strlen($audio) > 200) {
            file_put_contents($outputPath, $audio);
            return true;
        }

        return false;
    }

    /**
     * ElevenLabs API implementation (only runs if a valid user key is provided)
     */
    private function callElevenLabs(string $text, string $voice): ?string {
        $url = "https://api.elevenlabs.io/v1/text-to-speech/{$this->defaultVoice}";

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

        $audio = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($code === 200 && !empty($audio)) ? $audio : null;
    }
}
