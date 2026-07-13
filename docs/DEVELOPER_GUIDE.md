# Developer Guide

For anyone extending or modifying this codebase.

## Local setup

```bash
git clone <repo>
cd ai-chatbot-saas
composer install
cp .env.example .env
# edit .env: DB_*, JWT_SECRET (generate: php -r "echo bin2hex(random_bytes(32));"), GEMINI_API_KEY
php bin/migrate.php
php bin/seed.php   # set SEED_SUPER_ADMIN_* in .env first
php -S localhost:8000 -t public
```

## Architectural principles this codebase follows

1. **Controllers are thin.** They validate input (via a `FormRequest`), call exactly one service method, and shape the response. No business logic in a controller, ever.
2. **Services never receive raw request data.** Only validated arrays from a `FormRequest::validate()` call.
3. **Repositories are the only layer that talks SQL.** Even a service that needs a custom query calls `$repository->query()` (returns a `QueryBuilder`) rather than reaching for `Connection::get()` directly, except for genuinely one-off aggregate queries where a dedicated repository method would be overkill (see `AnalyticsService` for examples of the latter, deliberately).
4. **Every external dependency (AI provider, payment provider, notification channel) is behind an interface**, resolved through a factory. See `docs/GEMINI_INTEGRATION.md` for the pattern in detail — it's identical for `PaymentProviderInterface` and `NotificationChannelInterface`.
5. **Ownership checks happen in the service layer, not the controller.** `BotService::getForUser($uuid, $userId)` throws `NotFoundException` if the bot doesn't belong to that user — a controller never has to remember to check this itself.
6. **Mass assignment is prevented by explicit whitelisting**, not by a framework convention — every `update()` call site passes `array_intersect_key($data, array_flip([...]))`, never the raw validated array.

## Adding a new endpoint

1. Add a `FormRequest` in `app/Requests/{Resource}/` if the endpoint takes a body.
2. Add the business logic to an existing service, or create a new one in `app/Services/`.
3. Add a controller action (existing controller if it fits the resource, new one otherwise).
4. Register the route in `routes/api.php`, in the appropriate group, with the right middleware stack.
5. If the endpoint should count against a plan limit, add `UsageLimiterMiddleware::class . ':metric_name'`.
6. If it's admin-only, add both `RoleMiddleware::class . ':super-admin,admin'` (coarse) and `PermissionMiddleware::class . ':specific.permission'` (fine-grained) — see any existing `/admin/*` route for the pattern.
7. Document it in `docs/api.md`.

## Adding a new AI provider

See `docs/GEMINI_INTEGRATION.md`'s "Adding a second provider" section
— implement the two interfaces, add one `match` arm, done. Nothing
else changes.

## Adding a new migration

**Never edit a shipped migration.** Add a new one, following the
`YYYY_MM_DD_NNNNNN_description.php` filename convention. Look at any
existing migration for the `Blueprint`/`AlterBlueprint` DSL. Both
`up()` and `down()` are required — `down()` should genuinely reverse
`up()`, not just exist to satisfy the interface.

## Running tests

```bash
composer test
```

Runs `tests/Unit/` (pure-logic tests, no database needed) via
PHPUnit. See `tests/Feature/README.md` for why HTTP-level feature
tests aren't formalized into this suite yet, and how this project's
own development actually verified behavior instead (real HTTP
requests against real MySQL and a Gemini-API-compatible mock server).

## Debugging a request locally without a browser/Postman

The pattern used throughout this project's own development — a small
script that boots the app and dispatches a synthetic `Request`:

```php
<?php
require __DIR__ . '/vendor/autoload.php';
use App\Core\Http\Request;

$app = require __DIR__ . '/bootstrap/app.php';
$_SERVER = ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/bots', 'HTTP_HOST' => 'localhost', 'CONTENT_TYPE' => 'application/json'];
$request = new Request([], [], $_SERVER, [], json_encode(['name' => 'Test Bot']));
$response = $app->handle($request);
echo $response->getStatusCode() . "\n";
```

Set `APP_DEBUG=true` temporarily to see full stack traces in the
response body while debugging — **never** in production.

## Code style

`friendsofphp/php-cs-fixer` is a dev dependency. Match the existing
style you see in surrounding code: `declare(strict_types=1)` in every
file, constructor property promotion for dependency injection,
readonly properties on value objects, explicit return types
everywhere.

## Common pitfalls (found and fixed during this project's own development)

- **Bootstrapping a CLI script without `bootstrap/app.php`.** `Container::getInstance()` alone has no interface bindings registered — those only happen inside `bootstrap/app.php`. Always `require bootstrap/app.php` in any new `bin/` script; see `docs/CRON_JOBS.md`.
- **Cross-namespace class references without an explicit `use` import.** PHP resolves a bare class name relative to the *current* file's namespace, not where the class actually lives. This caused a real bug in `ChatOrchestratorService` (namespace `App\Services\AI`) referencing `WebhookDispatcherService` (namespace `App\Services`) — see `SECURITY.md`/`docs/architecture.md`. Always fully qualify or explicitly `use` a class from a different namespace, even one that "feels" closely related.
- **A column sized for one sentinel value breaking on a different one.** `usage_counters.period` was sized for `'YYYY-MM'` (7 chars) but the code also stores the literal `'lifetime'` (8 chars) — silent truncation errors. When adding a new sentinel/magic string value to an existing column, check the column's actual width, don't assume.
- **Blind `(bool)` casts on request input.** `(bool) "false"` is `true` in PHP — a non-empty string is always truthy. Use `filter_var($value, FILTER_VALIDATE_BOOLEAN)` (or better, run it through the `Validator`'s `boolean` rule, which correctly rejects ambiguous strings rather than mis-casting them).
