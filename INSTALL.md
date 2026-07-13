# Install

Quick-start version. For full detail on every step (including
Hostinger-specific instructions and troubleshooting), see
`docs/installation.md`.

```bash
# 1. Install dependencies
composer install

# 2. Configure environment
cp .env.example .env
# Edit .env: DB_*, JWT_SECRET (php -r "echo bin2hex(random_bytes(32));"),
# GEMINI_API_KEY, MAIL_*, CORS_ALLOWED_ORIGINS — see docs/ENVIRONMENT_VARIABLES.md

# 3. Create the database (MySQL 8+/MariaDB, utf8mb4)
mysql -u root -p -e "CREATE DATABASE ai_chatbot_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Run migrations
php bin/migrate.php

# 5. Seed roles/permissions/plans + an initial super admin
#    (set SEED_SUPER_ADMIN_EMAIL/PASSWORD in .env first)
php bin/seed.php

# 6. Point your web server at /public (see docs/installation.md if you can't)

# 7. Verify
curl http://localhost:8000/api/v1/health
```

For production deployment (Hostinger shared hosting or a VPS), see
`DEPLOYMENT.md` and work through `docs/PRODUCTION_CHECKLIST.md` before
going live.

## Requirements

- PHP 8.3+ with `pdo_mysql`, `curl`, `mbstring`, `json`, `zip`, `fileinfo`, `openssl`
- MySQL 8+ or MariaDB 10.6+
- Composer 2.x
- A Google Gemini API key (free tier available at Google AI Studio)
- An SMTP provider for outgoing email (password resets, notifications)

## Local development server

```bash
php -S localhost:8000 -t public
```

Not suitable for production (no concurrency, no process management) —
see `DEPLOYMENT.md` for a real deployment.
