// This Next.js version's static export writes each route's real HTML
// as a flat "<route>.html" file, while "<route>/" (created whenever
// some other route is nested under it, including dynamic children)
// holds only client-navigation payload files — never an index.html.
//
// That's fine for hosts that treat a directory-shaped URL as "no file
// here, try the rewrite rule" and move on. It is NOT fine on hosts
// (confirmed: Hostinger/LiteSpeed) that deny directory access — via
// their own "Options -Indexes"-equivalent — before a custom .htaccess
// RewriteRule ever gets a chance to redirect away from it, 403ing
// every route that has any nested route below it (which, in this
// export, is effectively every route).
//
// The fix that doesn't depend on the host's rewrite engine at all:
// give each such directory a real index.html — a copy of its sibling
// "<route>.html" — so the directory resolves via the server's stock,
// universally-supported DirectoryIndex behavior instead of ever
// needing a directory listing or a rewrite rule.
//
// Runs after `next build` (see package.json's build script).

import { copyFileSync, existsSync, readdirSync, statSync } from "node:fs";
import { join } from "node:path";

const outDir = new URL("../out", import.meta.url).pathname;

function walk(dir) {
  for (const entry of readdirSync(dir)) {
    const full = join(dir, entry);
    if (!statSync(full).isDirectory()) continue;

    const indexPath = join(full, "index.html");
    const siblingHtml = `${full}.html`;

    if (!existsSync(indexPath) && existsSync(siblingHtml)) {
      copyFileSync(siblingHtml, indexPath);
    }

    walk(full);
  }
}

if (!existsSync(outDir)) {
  console.error(`fix-static-export: ${outDir} does not exist — run "next build" first.`);
  process.exit(1);
}

walk(outDir);
console.log("fix-static-export: added index.html to route directories missing one.");
