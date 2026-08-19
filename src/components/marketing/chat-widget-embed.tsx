"use client";

import Script from "next/script";
import { env } from "@/lib/env";

/**
 * The live chat widget for this site's own bot — the same embed
 * snippet a customer would paste into their own site, dogfooded here.
 */
export function ChatWidgetEmbed() {
  if (!env.widgetBotId) return null;

  return (
    <Script
      id="primewebkit-widget"
      strategy="afterInteractive"
      dangerouslySetInnerHTML={{
        __html: `(function () {
  var s = document.createElement('script');
  s.src = "${env.apiUrl}/widget/${env.widgetBotId}/config";
  s.async = true;
  s.setAttribute('data-bot-id', "${env.widgetBotId}");
  document.body.appendChild(s);
})();`,
      }}
    />
  );
}
