# Feature Tests

This directory is reserved for HTTP-level feature tests (full request →
response, against a real test database) — the kind that need
`RefreshDatabase`-style setup/teardown per test.

## Current testing approach

This project's actual end-to-end verification — every phase of the
build — was performed via real HTTP requests against a real MySQL
instance and a local server replicating Google's Gemini API wire
protocol exactly (request/response shapes, SSE streaming, error
codes), run as standalone PHP scripts rather than a formal PHPUnit
feature-test suite. This caught real, reproducible bugs (documented
throughout `docs/architecture.md` and `SECURITY.md`) that a purely
mocked unit-test suite would not have surfaced — a query that works
against a mock repository but violates a real foreign-key constraint,
a container binding that's only registered in one bootstrap path, a
race condition that only manifests against a real database engine's
actual locking behavior, and so on.

## Why formal PHPUnit feature tests aren't included here yet

Building a proper `RefreshDatabase`-equivalent harness (migrate a
throwaway test database, run each test in a transaction that's rolled
back afterward, seed consistent fixture data) is real infrastructure
work independent of the application code itself, and wasn't the
highest-value use of remaining effort versus the alternative verification
approach already in use. `tests/Unit/` (see `SsrfGuardTest.php`,
`ValidatorTest.php`) covers pure-logic classes with no database
dependency, runnable via `composer test` with zero setup.

## Adding feature tests

If/when you build this out, the shape would look like:

```php
final class BotApiTest extends TestCase
{
    protected function setUp(): void
    {
        // Point DB_DATABASE at a dedicated test database, run migrations once per suite (not per test — too slow), truncate relevant tables per test.
    }

    public function testCreatingABotRequiresAuthentication(): void
    {
        $response = $this->post('/api/v1/bots', ['name' => 'Test Bot']);
        $this->assertSame(401, $response->getStatusCode());
    }

    // ...
}
```

You'll need a minimal `TestCase` base class that boots the app
(`bootstrap/app.php`) and dispatches a `Request` the same way
`public/index.php` does, then inspects the returned `Response` —
exactly the pattern the ad-hoc verification scripts used throughout
this project's development used, formalized into reusable test
infrastructure.
