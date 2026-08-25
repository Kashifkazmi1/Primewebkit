# Deploying the PrimeWebKit frontend

This app is a Next.js static export (`output: "export"` in `next.config.ts`) —
there's no Node.js process at runtime. `next build` produces a folder of
plain HTML/CSS/JS in `out/`, and that folder is what gets served.

## Automatic deploys (GitHub Actions → Hostinger)

`.github/workflows/deploy.yml` builds and deploys on every push to `main`:

1. `npm ci && npm run build` — produces `out/`.
2. `rsync -avz --delete out/ user@host:DEPLOY_PATH` over SSH — syncs the
   export directly into Hostinger's document root, removing files that no
   longer exist in the new build.

This requires four repository secrets (**Settings → Secrets and variables →
Actions**):

| Secret        | Value                                             |
| -------------- | -------------------------------------------------- |
| `SSH_KEY`      | Private key for an SSH user with access to Hostinger |
| `HOST`         | Hostinger SSH host                                  |
| `PORT`         | Hostinger SSH port                                  |
| `USERNAME`     | Hostinger SSH username                              |
| `DEPLOY_PATH`  | Absolute path to the domain's document root (e.g. `public_html`) |

Once those are set, pushing to `main` is the entire deploy — no manual
upload step.

## Manual deploy (if you ever need it)

```bash
npm ci
npm run build      # writes ./out
```

Upload the **contents** of `out/` (not the `out` folder itself) to your
host's document root, so `index.html` ends up at the web root. On
Hostinger via hPanel File Manager: zip the contents of `out/`, upload the
zip into `public_html`, then extract it there.

`public/.htaccess` is copied into `out/` automatically by `next build` and
handles security headers — make sure hidden files are visible in File
Manager so it doesn't get skipped during upload.

### Why `npm run build` also runs a script after `next build`

This Next.js version's static export writes each route's real page as a
flat `<route>.html` file — `<route>/` (created whenever some other route
is nested under it) holds only client-navigation payload files, never an
`index.html`. Most hosts don't care and fall through to a rewrite rule for
the directory-shaped URL, but some (confirmed: Hostinger/LiteSpeed) deny
access to a directory with no index file before any `.htaccess`
`RewriteRule` gets a chance to redirect away from it — 403ing every route
that has anything nested under it, which in this export is effectively
every route.

`scripts/fix-static-export.mjs` runs automatically after `next build`
(see the `build` script in `package.json`) and copies each such flat
`<route>.html` into `<route>/index.html`, so every route resolves via the
server's ordinary `DirectoryIndex` behavior instead of depending on a
rewrite rule at all. If you ever see a route 403 in production after a
correct upload, check whether `<route>/index.html` actually exists on the
server — if it doesn't, the upload didn't include it, or was built with
an older version of this script.

## Environment configuration

All runtime config is `NEXT_PUBLIC_*` and gets baked into the build at
build time — see `.env.example` for the full list and what each one does.
There are working defaults hardcoded in `src/lib/env.ts`, so the app
builds and runs even with no `.env` file present; override them by adding
a `.env.production` (not committed) before running `npm run build`, or by
setting the same variables as CI/CD environment variables.

To change the API URL, the Google OAuth client ID, or the upgrade/checkout
URL in production: update the env vars, then rebuild and redeploy — nothing
can be changed after the fact without a rebuild, since this is a static
export.

## ⚠️ Backend dependency: Google sign-in

`POST /auth/google` must exist on the backend (verifying the Google ID
token against Google's certs, matching `aud` against
`NEXT_PUBLIC_GOOGLE_CLIENT_ID`, and issuing the same JWT pair as
`/auth/login`) for the "Continue with Google" button to work. If that
endpoint isn't live yet, the button still renders, but a failed sign-in
attempt shows a toast error and falls back to email/password — it never
crashes the page.

## Post-deploy checklist

- [ ] `/` , a pillar page (e.g. `/ai-customer-support-agent`), a blog post,
      and `/docs/install` all load.
- [ ] `/login` renders the Google button and email/password form; a bad
      password shows a field-level error, not a crash.
- [ ] `/sitemap.xml` and `/robots.txt` are reachable and reference the
      production domain.
- [ ] Signing in and viewing `/dashboard` shows real API calls to
      `NEXT_PUBLIC_API_URL` in the Network tab, with no CORS errors — the
      API's `CORS_ALLOWED_ORIGINS` must include this frontend's origin.
- [ ] Every "Upgrade" button opens `NEXT_PUBLIC_UPGRADE_BASIC_URL` in a new
      tab — there should be no in-app payment form anywhere.
