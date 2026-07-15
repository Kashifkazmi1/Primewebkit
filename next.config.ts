import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Static export: this frontend deploys to plain shared hosting (no
  // Node.js process on the server) as a folder of HTML/CSS/JS files.
  // `headers()`/`redirects()`/`rewrites()` aren't supported in this mode
  // since there's no server to apply them at request time — the
  // equivalent security headers live in public/.htaccess instead, which
  // `next build` copies straight into the exported output.
  output: "export",
  poweredByHeader: false,
  images: {
    unoptimized: true,
  },
};

export default nextConfig;
