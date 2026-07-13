<?php

declare(strict_types=1);

namespace App\Core\Security;

use App\Exceptions\ValidationException;

/**
 * Blocks Server-Side Request Forgery: this platform makes outbound
 * HTTP requests to URLs *supplied by users* in two places — the
 * website-knowledge-base crawler (WebsiteCrawlerService) and outgoing
 * webhook delivery (WebhookService / WebhookDispatcherService).
 * Without this check, a malicious bot owner could point either
 * feature at an internal service (a cloud metadata endpoint, an
 * internal admin panel, localhost-bound services on the same host)
 * and use the platform as a proxy to reach it.
 *
 * This resolves the hostname and rejects private/loopback/link-local/
 * reserved ranges and the cloud metadata address, for every resolved
 * IP (a hostname can resolve to multiple addresses). It is called
 * both at registration time (immediate feedback) and again
 * immediately before each actual request (defense against the target
 * DNS record changing after registration — "DNS rebinding"). A small
 * TOCTOU window remains between this check and the actual connection
 * (full protection would require pinning the resolved IP via
 * `CURLOPT_RESOLVE`, which is a reasonable future hardening step, not
 * implemented here to keep `ExternalHttpClient` provider-agnostic and
 * simple); this check still eliminates the overwhelmingly common case.
 */
final class SsrfGuard
{
    /**
     * @throws ValidationException if the URL is not safe to request
     */
    public static function assertSafeUrl(string $url, string $fieldName = 'url'): void
    {
        $parts = parse_url($url);

        if ($parts === false || empty($parts['host']) || empty($parts['scheme'])) {
            throw new ValidationException([$fieldName => ['The URL is not valid.']]);
        }

        $scheme = strtolower($parts['scheme']);

        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new ValidationException([$fieldName => ['Only http and https URLs are allowed.']]);
        }

        $host = $parts['host'];

        // Reject IP-literal loopback/private ranges directly (covers
        // both IPv4 and IPv6 without needing a DNS lookup).
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            self::assertPublicIp($host, $fieldName);

            return;
        }

        if (strcasecmp($host, 'localhost') === 0 || str_ends_with(strtolower($host), '.localhost')) {
            throw new ValidationException([$fieldName => ['This URL points to a local address and is not allowed.']]);
        }

        $resolvedIps = self::resolveAllIps($host);

        if (empty($resolvedIps)) {
            throw new ValidationException([$fieldName => ['The URL host could not be resolved.']]);
        }

        foreach ($resolvedIps as $ip) {
            self::assertPublicIp($ip, $fieldName);
        }
    }

    /**
     * @return list<string>
     */
    private static function resolveAllIps(string $host): array
    {
        $ips = [];

        $recordsA = @dns_get_record($host, DNS_A);
        $recordsAAAA = @dns_get_record($host, DNS_AAAA);

        foreach (array_merge($recordsA ?: [], $recordsAAAA ?: []) as $record) {
            if (isset($record['ip'])) {
                $ips[] = $record['ip'];
            } elseif (isset($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        if (empty($ips)) {
            $fallback = gethostbyname($host);

            if ($fallback !== $host && filter_var($fallback, FILTER_VALIDATE_IP) !== false) {
                $ips[] = $fallback;
            }
        }

        return array_unique($ips);
    }

    private static function assertPublicIp(string $ip, string $fieldName): void
    {
        if (self::isDisallowedIp($ip)) {
            throw new ValidationException([$fieldName => ['This URL points to a private, local, or reserved address and is not allowed.']]);
        }
    }

    private static function isDisallowedIp(string $ip): bool
    {
        // Cloud metadata endpoints — the single most common SSRF target.
        if ($ip === '169.254.169.254' || $ip === 'fd00:ec2::254') {
            return true;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return str_starts_with($ip, '127.');
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return $ip === '::1';
        }

        return false;
    }
}
