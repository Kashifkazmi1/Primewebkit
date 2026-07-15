"use client";

import Script from "next/script";
import { useEffect, useId, useRef } from "react";
import { env } from "@/lib/env";

declare global {
  interface Window {
    google?: {
      accounts: {
        id: {
          initialize: (config: {
            client_id: string;
            callback: (response: { credential: string }) => void;
          }) => void;
          renderButton: (parent: HTMLElement, options: Record<string, unknown>) => void;
        };
      };
    };
  }
}

export function GoogleSignInButton({
  onCredential,
  disabled,
}: {
  onCredential: (credential: string) => void;
  disabled?: boolean;
}) {
  const containerRef = useRef<HTMLDivElement>(null);
  const domId = useId();

  useEffect(() => {
    if (!env.googleClientId || disabled) return;

    let cancelled = false;

    function render() {
      if (cancelled || !window.google || !containerRef.current) return;
      window.google.accounts.id.initialize({
        client_id: env.googleClientId,
        callback: (response) => onCredential(response.credential),
      });
      containerRef.current.innerHTML = "";
      window.google.accounts.id.renderButton(containerRef.current, {
        type: "standard",
        theme: "outline",
        size: "large",
        shape: "pill",
        width: 360,
        text: "continue_with",
      });
    }

    if (window.google) {
      render();
    } else {
      const interval = setInterval(() => {
        if (window.google) {
          clearInterval(interval);
          render();
        }
      }, 100);
      return () => {
        cancelled = true;
        clearInterval(interval);
      };
    }
  }, [onCredential, disabled]);

  if (!env.googleClientId) return null;

  return (
    <>
      <Script src="https://accounts.google.com/gsi/client" strategy="afterInteractive" />
      <div id={domId} ref={containerRef} className="flex w-full justify-center" />
    </>
  );
}
