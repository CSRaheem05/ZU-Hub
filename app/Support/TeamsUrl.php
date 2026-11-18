<?php

namespace App\Support;

/**
 * Microsoft Teams meeting links. ZU Hub does not create meetings (no Microsoft Graph): the instructor pastes the
 * join link from their Teams invitation, and students are sent to exactly that link.
 */
class TeamsUrl
{
    /** Hosts Microsoft uses for meeting links (commercial, personal and government clouds). */
    private const HOSTS = ['teams.microsoft.com', 'teams.live.com', 'teams.microsoft.us', 'gov.teams.microsoft.us', 'dod.teams.microsoft.us'];

    public static function isValid(?string $url): bool
    {
        if (! is_string($url) || $url === '' || strlen($url) > 2000) {
            return false;
        }

        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');

        return ($parts['scheme'] ?? '') === 'https'
            && ! isset($parts['user'], $parts['pass'])
            && in_array($host, self::HOSTS, true);
    }
}
