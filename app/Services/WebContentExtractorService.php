<?php

namespace App\Services;

class WebContentExtractorService
{
    public function extractCleanContent(string $html): array
    {
        // Remove script, style, nav, footer, header tags
        $html = preg_replace('#<script[^>]*>.*?</script>#si', '', $html);
        $html = preg_replace('#<style[^>]*>.*?</style>#si', '', $html);
        $html = preg_replace('#<nav[^>]*>.*?</nav>#si', '', $html);
        $html = preg_replace('#<footer[^>]*>.*?</footer>#si', '', $html);
        $html = preg_replace('#<header[^>]*>.*?</header>#si', '', $html);
        $html = preg_replace('#<aside[^>]*>.*?</aside>#si', '', $html);
        $html = preg_replace('#<!--.*?-->#s', '', $html);

        // Extract title
        $title = '';
        if (preg_match('#<title[^>]*>(.*?)</title>#si', $html, $m)) {
            $title = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
        }

        // Try to get main content area
        $content = $html;
        if (preg_match('#<main[^>]*>(.*?)</main>#si', $html, $m)) {
            $content = $m[1];
        } elseif (preg_match('#<article[^>]*>(.*?)</article>#si', $html, $m)) {
            $content = $m[1];
        } elseif (preg_match('#<div[^>]*(?:class|id)=["\'][^"\']*(?:content|main|body)[^"\']*["\'][^>]*>(.*?)</div>#si', $html, $m)) {
            $content = $m[1];
        }

        // Strip remaining HTML
        $text = strip_tags($content);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = trim($text);

        return [
            'title' => $title,
            'content' => $text,
        ];
    }

    public function extractLinks(string $html, string $baseUrl): array
    {
        $links = [];
        $baseParts = parse_url($baseUrl);
        $baseHost = $baseParts['host'] ?? '';
        $baseScheme = $baseParts['scheme'] ?? 'https';

        preg_match_all('#<a[^>]+href=["\']([^"\']+)["\']#i', $html, $matches);

        foreach ($matches[1] as $href) {
            $href = trim($href);

            // Skip anchors, javascript, mailto
            if (str_starts_with($href, '#') || str_starts_with($href, 'javascript:') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                continue;
            }

            // Resolve relative URLs
            if (str_starts_with($href, '//')) {
                $href = $baseScheme . ':' . $href;
            } elseif (str_starts_with($href, '/')) {
                $href = $baseScheme . '://' . $baseHost . $href;
            } elseif (!preg_match('#^https?://#', $href)) {
                $href = rtrim($baseUrl, '/') . '/' . $href;
            }

            // Only same-domain links
            $hrefParts = parse_url($href);
            $hrefHost = $hrefParts['host'] ?? '';
            if ($hrefHost !== $baseHost) {
                continue;
            }

            // Remove fragment and normalize
            $href = strtok($href, '#');
            $href = rtrim($href, '/');

            if (!empty($href)) {
                $links[] = $href;
            }
        }

        return array_unique($links);
    }

    public function parseRobotsTxt(string $robotsTxt): array
    {
        $disallowed = [];
        $lines = explode("\n", $robotsTxt);
        $isForUs = false;

        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with(strtolower($line), 'user-agent:')) {
                $agent = trim(substr($line, 11));
                $isForUs = ($agent === '*');
            } elseif ($isForUs && str_starts_with(strtolower($line), 'disallow:')) {
                $path = trim(substr($line, 9));
                if (!empty($path)) {
                    $disallowed[] = $path;
                }
            }
        }

        return $disallowed;
    }

    public function isAllowed(string $url, array $disallowedPaths): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '/';

        foreach ($disallowedPaths as $disallowed) {
            if (str_starts_with($path, $disallowed)) {
                return false;
            }
        }

        return true;
    }
}
