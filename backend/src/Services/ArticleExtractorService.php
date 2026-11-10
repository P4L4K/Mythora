<?php
/**
 * MYTHORA - Web Article Extractor Service
 * Fetches webpage HTML, extracts meaningful body content, strips navigation, ads, headers, footers
 */

namespace App\Services;

class ArticleExtractorService {
    /**
     * Fetches and cleans readable text from an article URL
     */
    public static function extractContent(string $url): ?string {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $html = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code < 200 || $code >= 400 || empty($html)) {
            return null;
        }

        // Clean out noisy tags (scripts, styles, nav, headers, footers, svg, comments)
        $html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);
        $html = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $html);
        $html = preg_replace('#<nav(.*?)>(.*?)</nav>#is', '', $html);
        $html = preg_replace('#<header(.*?)>(.*?)</header>#is', '', $html);
        $html = preg_replace('#<footer(.*?)>(.*?)</footer >#is', '', $html);
        $html = preg_replace('#<aside(.*?)>(.*?)</aside>#is', '', $html);
        $html = preg_replace('#<!--(.*?)-->#is', '', $html);

        // Convert common block elements to newlines
        $html = preg_replace('#<(h[1-6]|p|div|section|article|li)[^>]*>#i', "\n", $html);
        $text = strip_tags($html);

        // Normalize whitespaces and filter lines
        $lines = explode("\n", $text);
        $meaningful = [];
        foreach ($lines as $line) {
            $trimmed = trim(html_entity_decode($line, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            // Keep sentences with actual prose content (at least 25 characters)
            if (strlen($trimmed) >= 25 && !preg_match('#(cookie|privacy policy|terms of service|copyright|sign in|log in|subscribe)#i', $trimmed)) {
                $meaningful[] = $trimmed;
            }
        }

        $content = implode("\n\n", array_slice($meaningful, 0, 30));
        return !empty($content) ? mb_substr($content, 0, 5000) : null;
    }
}

