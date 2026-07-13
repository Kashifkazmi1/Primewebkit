# Deployment Guide

Two deployment targets: Hostinger shared hosting (the platform this
project was built for) and a generic VPS (more control, more
responsibility). Read `docs/PRODUCTION_CHECKLIST.md` alongside this —
this document is "how," the checklist is "did you remember."

---

## Hostinger Shared Hosting

### 1. Prerequisites

- A Hostinger plan with PHP 8.3+ support and SSH access
- A MySQL database provisioned via hPanel → Databases
- A domain or subdomain pointed at the hosting account

### 2. Upload the code

Via SSH (preferred):
```bash
ssh u123456789@yourdomain.com
cd domains/yourdomain.com
git clone <your-repo-url> .
composer install --no-dev --optimize-autoloader
```

If Composer isn't available via SSH on your plan, run `composer install`
locally and upload the resulting `vendor/` directory along with the
rest of the code.

### 3. Set the document root

hPanel → Domains → [yourdomain] → check for a "Document Root" setting.
**Set it to the `public/` folder** if your plan allows it — more
secure, since everything outside `public/` becomes inaccessible to
the web server by construction.

If your plan doesn't allow changing the document root, the project
root's `.htaccess` handles this for you instead — it blocks direct
access to everything except `public/` and routes requests through it.

### 4. Configure `.env`

```bash
cp .env.example .env
nano .env
```

Fill in real values — see `docs/ENVIRONMENT_VARIABLES.md`. At
minimum: `DB_*`, `JWT_SECRET` (generate fresh), `GEMINI_API_KEY`,
`MAIL_*`, `CORS_ALLOWED_ORIGINS`.

### 5. Run migrations and seeders

```bash
php bin/migrate.php
php bin/seed.php
```

### 6. Set up cron jobs

hPanel → Advanced → Cron Jobs. Add all three (exact lines in
`docs/CRON_JOBS.md`):
```
*/5 * * * * php /home/u123456789/domains/yourdomain.com/bin/process-crawl-jobs.php 5 >> /home/u123456789/domains/yourdomain.com/storage/Logs/crawl-cron.log 2>&1
*/15 * * * * php /home/u123456789/domains/yourdomain.com/bin/process-webhook-retries.php >> /home/u123456789/domains/yourdomain.com/storage/Logs/webhook-cron.log 2>&1
0 2 * * * php /home/u123456789/domains/yourdomain.com/bin/process-billing-cycle.php >> /home/u123456789/domains/yourdomain.com/storage/Logs/billing-cron.log 2>&1
```

(Replace the path with your actual account path.)

### 7. SSL/HTTPS

hPanel → SSL → issue a free Let's Encrypt certificate and enable
"Force HTTPS" if offered — the app's own `.htaccess` also redirects
`http`→`https` as a second layer.

### 8. PHP configuration

hPanel → Advanced → PHP Configuration → select PHP 8.3, set the
values from `docs/PHP_CONFIGURATION.md` where the UI exposes them.

### 9. Verify

```bash
curl https://yourdomain.com/api/v1/health
```

Then work through `docs/PRODUCTION_CHECKLIST.md`'s "Final smoke test."

---

## VPS Deployment

More setup work, more control (real cron via `crontab`, ability to
tune MySQL/PHP-FPM/nginx directly, room to grow beyond the shipped
architecture if needed).

### 1. Provision the server

Ubuntu 22.04/24.04 LTS. Minimum realistic spec: 2 vCPU, 4GB RAM,
40GB disk.

### 2. Install the stack

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server php8.3-fpm php8.3-mysql php8.3-curl \
  php8.3-mbstring php8.3-xml php8.3-zip php8.3-gd unzip git

curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 3. Secure MySQL

```bash
sudo mysql_secure_installation
sudo mysql -e "CREATE DATABASE ai_chatbot_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'ai_chatbot_user'@'localhost' IDENTIFIED BY 'REPLACE_WITH_STRONG_PASSWORD';"
sudo mysql -e "GRANT ALL PRIVILEGES ON ai_chatbot_saas.* TO 'ai_chatbot_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

### 4. Deploy the code

```bash
sudo mkdir -p /var/www/ai-chatbot-saas
sudo chown -R $USER:www-data /var/www/ai-chatbot-saas
cd /var/www/ai-chatbot-saas
git clone <your-repo-url> .
composer install --no-dev --optimize-autoloader
cp .env.example .env
nano .env
php bin/migrate.php
php bin/seed.php
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/
```

### 5. Configure nginx

The frontend (`portal/`) and this backend are **two separate
applications on two separate subdomains** — `primewebkit.com` for the
Next.js frontend, `api.primewebkit.com` for this PHP API — not one
domain split by path. That means two independent, ordinary nginx
server blocks; neither needs to know about the other.

**Backend — `api.primewebkit.com`** (this is the existing config,
unchanged apart from `server_name`):

```nginx
server {
    listen 80;
    server_name api.primewebkit.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.primewebkit.com;
    root /var/www/ai-chatbot-saas/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/api.primewebkit.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.primewebkit.com/privkey.pem;

    location /api/v1/widget/ {
        proxy_buffering off;
        try_files $uri $uri/ /index.php?$query_string;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_read_timeout 60;
    }

    location ~ /\. {
        deny all;
    }

    location ~ ^/(app|bootstrap|config|database|routes|storage|vendor|docs|tests)/ {
        deny all;
    }
}
```

**Critical for streaming**: `proxy_buffering off` on the widget
location block — without it, nginx buffers the entire SSE response
before forwarding it, defeating streaming entirely.

**Frontend — `primewebkit.com`** (new — a plain reverse proxy to a
long-lived Next.js process, no PHP involved):

```nginx
server {
    listen 80;
    server_name primewebkit.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name primewebkit.com;

    ssl_certificate     /etc/letsencrypt/live/primewebkit.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/primewebkit.com/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Both subdomains need their own TLS certificate — `certbot --nginx -d
primewebkit.com -d api.primewebkit.com` in one call will issue both if
they're on the same server, or run certbot separately per host if not.

**Running the frontend process** (from `portal/`, wherever it's
hosted — can be the same server as the backend, or a completely
different one, since it only talks to the backend over HTTPS):
```bash
npm ci && npm run build
pm2 start npm --name primewebkit-portal -- start
pm2 save
```

**Where the frontend can live**: because it's a fully separate origin
that only calls the backend's public HTTPS API, `portal/` can be
deployed anywhere that runs Node — this same VPS, a different VPS, or
a platform like Vercel/Render. It does *not* need to share a server
with the PHP backend the way a single-domain, path-routed setup would.
The one thing that **can't** run it is classic PHP-only shared
hosting (no persistent Node process) — the app uses server-rendered
route handlers (`sitemap.xml`, `robots.txt`, `llms.txt`, the generated
OG image) that need a live Node runtime, not just static files.

### 6. SSL via Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

### 7. Cron jobs

```bash
crontab -e -u www-data
```
```
*/5 * * * * php /var/www/ai-chatbot-saas/bin/process-crawl-jobs.php 5 >> /var/www/ai-chatbot-saas/storage/Logs/crawl-cron.log 2>&1
*/15 * * * * php /var/www/ai-chatbot-saas/bin/process-webhook-retries.php >> /var/www/ai-chatbot-saas/storage/Logs/webhook-cron.log 2>&1
0 2 * * * php /var/www/ai-chatbot-saas/bin/process-billing-cycle.php >> /var/www/ai-chatbot-saas/storage/Logs/billing-cron.log 2>&1
```

### 8. PHP-FPM tuning

Edit `/etc/php/8.3/fpm/php.ini` per `docs/PHP_CONFIGURATION.md`, then:
```bash
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

### 9. Firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

### 10. Verify

Same as shared hosting: `curl https://yourdomain.com/api/v1/health`,
then the full smoke test in `docs/PRODUCTION_CHECKLIST.md`.

---

## Choosing between them

| | Hostinger Shared | VPS |
|---|---|---|
| Setup time | ~30 minutes | 1–3 hours |
| Cron | hPanel UI | `crontab` directly |
| Scaling ceiling | Limited by shared plan resources | Whatever you provision |
| Maintenance burden | Host manages the OS/PHP/MySQL | You manage all of it |
| Cost | Lower | Higher |

For most launches, start on shared hosting — the entire platform was
built with that as the primary target and works there without
compromise. Move to a VPS when you specifically need something shared
hosting can't provide.
