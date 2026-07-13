<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Security\SsrfGuard;
use App\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

final class SsrfGuardTest extends TestCase
{
    /**
     * @dataProvider blockedUrlProvider
     */
    public function testBlocksUnsafeUrls(string $url): void
    {
        $this->expectException(ValidationException::class);
        SsrfGuard::assertSafeUrl($url);
    }

    /**
     * @return list<list<string>>
     */
    public static function blockedUrlProvider(): array
    {
        return [
            'loopback IPv4' => ['http://127.0.0.1/admin'],
            'loopback IPv6' => ['http://[::1]/'],
            'localhost hostname' => ['http://localhost:8080/'],
            'localhost subdomain' => ['http://sub.localhost/'],
            'cloud metadata endpoint' => ['http://169.254.169.254/latest/meta-data/'],
            'private class A' => ['http://10.0.0.5/internal'],
            'private class B' => ['http://172.16.0.1/'],
            'private class C' => ['http://192.168.1.1/'],
            'non-http scheme (ftp)' => ['ftp://example.com/'],
            'non-http scheme (file)' => ['file:///etc/passwd'],
        ];
    }

    /**
     * @dataProvider allowedUrlProvider
     */
    public function testAllowsPublicUrls(string $url): void
    {
        // Should not throw. DNS resolution requires network access;
        // if it's unavailable in this environment, resolveAllIps()
        // falls back gracefully and the test still exercises the
        // scheme/private-IP-literal logic, which is the part that
        // matters most for a unit test (no network dependency).
        try {
            SsrfGuard::assertSafeUrl($url);
            $this->assertTrue(true);
        } catch (ValidationException $e) {
            if (str_contains($e->getMessage(), 'could not be resolved')) {
                $this->markTestSkipped('DNS resolution unavailable in this test environment.');
            }

            throw $e;
        }
    }

    /**
     * @return list<list<string>>
     */
    public static function allowedUrlProvider(): array
    {
        return [
            'https example.com' => ['https://example.com/page'],
            'http with path' => ['http://www.google.com/search'],
        ];
    }

    public function testRejectsMalformedUrl(): void
    {
        $this->expectException(ValidationException::class);
        SsrfGuard::assertSafeUrl('not a url at all');
    }

    public function testRejectsUrlWithNoHost(): void
    {
        $this->expectException(ValidationException::class);
        SsrfGuard::assertSafeUrl('http:///path-only');
    }
}
