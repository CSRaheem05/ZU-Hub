<?php

namespace App\Support;

/**
 * How an external link is shown to students: a privacy-friendly embedded player when it is a YouTube video, otherwise
 * a plain "open in a new tab" link. Nothing else is ever embedded (no other sites, no external media files), and only
 * http(s) links are considered.
 */
class VideoEmbed
{
    /** @return array{embed:?string,direct:bool} */
    public static function resolve(?string $url): array
    {
        $none = ['embed' => null, 'direct' => false];
        $parts = is_string($url) ? parse_url($url) : false;

        if (! $parts || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return $none;
        }

        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        $id = null;

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtu.be'], true)) {
            if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
                $id = trim($path, '/');
            } elseif ($path === '/watch') {
                parse_str($parts['query'] ?? '', $query);
                $id = $query['v'] ?? null;
            } elseif (preg_match('~^/(embed|shorts|live)/([A-Za-z0-9_-]{11})~', $path, $m)) {
                $id = $m[2];
            }

            if (is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) {
                return ['embed' => 'https://www.youtube-nocookie.com/embed/'.$id, 'direct' => false];
            }
        }

        return $none;
    }
}
