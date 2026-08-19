import Script from "next/script";
import { env } from "@/lib/env";

/**
 * The live chat widget for this site's own bot — the same embed
 * snippet a customer would paste into their own site, dogfooded here.
 */
export function ChatWidgetEmbed() {
  if (!env.widgetBotId) return null;

  return <Script src={env.widgetJsUrl} data-bot-id={env.widgetBotId} strategy="afterInteractive" async />;
}
