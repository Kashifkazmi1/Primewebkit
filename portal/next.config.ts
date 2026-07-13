import type { NextConfig } from "next";

const baseSecurityHeaders = [
  { key: "X-Content-Type-Options", value: "nosniff" },
  { key: "Referrer-Policy", value: "strict-origin-when-cross-origin" },
  { key: "Permissions-Policy", value: "camera=(), microphone=(), geolocation=()" },
];

const nextConfig: NextConfig = {
  poweredByHeader: false,
  async headers() {
    return [
      // /chat is embeddable in an iframe on customer sites, so it's kept
      // out of this catch-all and never gets X-Frame-Options: SAMEORIGIN.
      { source: "/((?!chat).*)", headers: [...baseSecurityHeaders, { key: "X-Frame-Options", value: "SAMEORIGIN" }] },
      { source: "/chat/:path*", headers: baseSecurityHeaders },
    ];
  },
};

export default nextConfig;
