# PHP Configuration Recommendations

Recommended `php.ini` values for production. On Hostinger shared
hosting, most of these are set via hPanel → Advanced → PHP
Configuration (a per-domain UI, not a raw `php.ini` you edit
directly); on a VPS, edit `php.ini` directly and restart PHP-FPM.

## Required extensions

```
pdo_mysql, curl, mbstring, json, zip, fileinfo, openssl
```

All are enabled by default on Hostinger's PHP 8.3 profile. On a
self-managed VPS: `sudo apt install php8.3-{mysql,curl,mbstring,zip,gd}`
(json, fileinfo, openssl are core/bundled).

## Core settings

| Setting | Recommended | Why |
|---|---|---|
| `memory_limit` | `256M` | Document text extraction (large PDFs) and embedding batch processing are the most memory-hungry operations. 128M is workable for light use; 256M gives headroom. |
| `max_execution_time` | `60` | Most requests finish in well under a second; AI chat requests (non-streaming) can take several seconds waiting on Gemini. 60s covers this with margin. Streaming requests are not bound by this in the same way (see note below). |
| `upload_max_filesize` | `20M` | Matches `config/uploads.php`'s default document-upload limit. Raise both together if you need larger PDFs. |
| `post_max_size` | `25M` | Must be **larger** than `upload_max_filesize` (PHP silently truncates the upload otherwise) — a few MB of headroom for multipart overhead. |
| `max_input_time` | `60` | Time to parse POST data; matches `max_execution_time`. |
| `default_socket_timeout` | `30` | Affects raw socket operations; cURL (used for Gemini/webhooks/crawling) has its own explicit timeouts set in code and isn't affected by this. |

**Streaming note**: `POST /widget/{botUuid}/messages/stream` holds the
connection open for the duration of generation via Server-Sent Events.
`max_execution_time` in CLI/FPM contexts is measured as *script*
execution time, but a long-held streaming response can still hit web
server-level timeouts (Apache's `Timeout` directive, or a reverse
proxy's read timeout) independently of PHP's own setting. If you see
streaming responses cut off after a fixed duration, check
Apache's/nginx's timeout **in addition to** `php.ini`.

## opcache (strongly recommended)

```ini
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.validate_timestamps=1
opcache.revalidate_freq=2
```

`validate_timestamps=1` + a short `revalidate_freq` means opcache
still picks up deployed file changes within ~2 seconds, while getting
the bulk of the performance benefit. If you deploy rarely and want
maximum performance, set `validate_timestamps=0` and clear opcache
manually on deploy (`opcache_reset()` via a one-off script, or a
PHP-FPM reload) — not necessary for a first launch.

## Session handling

This platform does not use PHP sessions for API authentication (JWT
Bearer tokens only — see `SECURITY.md`) — `session.*` settings are
not relevant to its own security posture. Leave Hostinger's defaults.

## Error display

```ini
display_errors=Off
log_errors=On
error_reporting=E_ALL & ~E_DEPRECATED & ~E_STRICT
```

`display_errors=Off` is required in production regardless of
`APP_DEBUG` — a PHP-level fatal error *before* the application's own
exception handler is registered (a syntax error in a config file, for
instance) would otherwise print a raw stack trace straight to the
HTTP response, bypassing the app's own debug-gating entirely.

## Disabled functions (defense in depth)

Not required (the codebase never calls any of these — verified in
`SECURITY.md`'s audit), but a reasonable additional hardening layer if
your hosting plan allows configuring `disable_functions`:

```ini
disable_functions=exec,passthru,shell_exec,system,proc_open,popen
```

Hostinger shared hosting typically has these disabled by default
already. On a self-managed VPS, add this to `php.ini` explicitly.
