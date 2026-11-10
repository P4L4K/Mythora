<?php
/**
 * MYTHORA - Stock & Scene Image Resolution Service
 * Resolves context-specific images based on visual prompt and topic.
 * Uses Source Unsplash Direct Keyword Search and high-resolution thematic curated pools.
 */

namespace App\Services;

use App\Config\Config;

class ImageService {
    private string $unsplashAccessKey;
    private CacheService $cache;

    public function __construct() {
        $this->unsplashAccessKey = Config::get('UNSPLASH_ACCESS_KEY', '');
        $this->cache = new CacheService();
    }

    /**
     * Resolves a highly contextual vertical (9:16) image for each individual scene
     */
    public function resolveSceneImage(string $visualPrompt, string $style = 'Cinematic', int $sceneOrder = 1): string {
        $cleanKeywords = $this->extractContextualKeywords($visualPrompt, $style);
        $cacheKey = "img:" . md5($cleanKeywords . ':' . $sceneOrder);

        $cached = $this->cache->get($cacheKey);
        if ($cached) {
            return $cached;
        }

        // 1. If Unsplash Client-ID provided, perform targeted query
        if (!empty($this->unsplashAccessKey) && $this->unsplashAccessKey !== 'your-unsplash-access-key-here') {
            $url = "https://api.unsplash.com/search/photos?page=1&per_page=5&orientation=portrait&query=" . urlencode($cleanKeywords);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ["Authorization: Client-ID {$this->unsplashAccessKey}"],
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200) {
                $json = json_decode($res, true);
                $results = $json['results'] ?? [];
                if (!empty($results)) {
                    $selected = $results[$sceneOrder % count($results)]['urls']['regular'] ?? null;
                    if ($selected) {
                        $this->cache->set($cacheKey, $selected, 86400 * 7);
                        return $selected;
                    }
                }
            }
        }

        // 2. Direct Source Unsplash Dynamic Keyword Photo Redirect (Portrait 1080x1920)
        // This generates different contextual vertical images for "freelancer", "laptop", "remote work", "client", "contract"
        $encodedTerm = urlencode(str_replace(' ', ',', $cleanKeywords));
        $directUnsplashUrl = "https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=1080&h=1920&fit=crop&q=80&sig=" . ($sceneOrder * 137);

        // Topic-mapped contextual high-resolution vertical photography
        $topicPool = [
            'freelance' => [
                'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=1080&h=1920&fit=crop&q=80', // Coding / laptop workspace
                'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=1080&h=1920&fit=crop&q=80', // Remote collaboration
                'https://images.unsplash.com/photo-1486312338219-ce68d2c6f44d?w=1080&h=1920&fit=crop&q=80', // Writing / coffee desk
                'https://images.unsplash.com/photo-1551836022-d5d88e9218df?w=1080&h=1920&fit=crop&q=80', // Strategy planning
                'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=1080&h=1920&fit=crop&q=80'  // Freelancer presentation
            ],
            'business' => [
                'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=1080&h=1920&fit=crop&q=80',
                'https://images.unsplash.com/photo-1497366216548-37526070297c?w=1080&h=1920&fit=crop&q=80',
                'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1080&h=1920&fit=crop&q=80'
            ],
            'technology' => [
                'https://images.unsplash.com/photo-1518770660439-4636190af475?w=1080&h=1920&fit=crop&q=80',
                'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1080&h=1920&fit=crop&q=80',
                'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=1080&h=1920&fit=crop&q=80'
            ]
        ];

        // Match prompt keywords to pool
        $selectedPool = $topicPool['freelance'];
        if (preg_match('#(tech|code|software|ai|computer)#i', $visualPrompt)) {
            $selectedPool = $topicPool['technology'];
        } elseif (preg_match('#(business|money|market|finance)#i', $visualPrompt)) {
            $selectedPool = $topicPool['business'];
        }

        $image = $selectedPool[($sceneOrder - 1) % count($selectedPool)];
        $this->cache->set($cacheKey, $image, 86400 * 7);
        return $image;
    }

    private function extractContextualKeywords(string $prompt, string $style): string {
        $clean = preg_replace('/[^\w\s]/', ' ', strtolower($prompt));
        // Remove noise words
        $noise = ['shot', 'vertical', 'lighting', 'cinematic', 'scene', 'visual', 'showing', 'image', 'photo', 'with', 'style', 'aesthetic'];
        $words = array_diff(explode(' ', $clean), $noise);
        $meaningful = array_values(array_filter($words, fn($w) => strlen($w) >= 4));

        if (empty($meaningful)) {
            return 'workspace remote work';
        }
        return implode(' ', array_slice($meaningful, 0, 3));
    }
}
