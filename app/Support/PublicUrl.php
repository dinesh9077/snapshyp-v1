<?php

namespace App\Support;

/**
 * Seller-supplied URLs (outgoing webhooks) must point at the public internet, never at
 * this server, the private network or cloud metadata (169.254.169.254) — otherwise a
 * seller could make the server fetch internal URLs for them (SSRF).
 */
class PublicUrl
{
    public static function isSafe(?string $url): bool
    {
        $parts = parse_url((string) $url);
        if (!$parts || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host'])) {
            return false;
        }

        $host = trim($parts['host'], '[]');
        if (in_array(strtolower($host), ['localhost', 'localhost.localdomain'], true)) {
            return false;
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : self::resolve($host);
        if (empty($ips)) {
            return false;
        }

        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }

    private static function resolve(string $host): array
    {
        $ips = gethostbynamel($host) ?: [];
        foreach ((array) @dns_get_record($host, DNS_AAAA) as $record) {
            if (!empty($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }
        return $ips;
    }
}
