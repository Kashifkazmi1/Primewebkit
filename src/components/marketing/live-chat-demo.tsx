"use client";

import { Bot, Send, Sparkles } from "lucide-react";
import { useEffect, useState } from "react";

const script: { role: "user" | "bot"; text: string }[] = [
  { role: "user", text: "Do you offer refunds?" },
  { role: "bot", text: "Yes — refunds are available within 14 days of purchase, no questions asked. Want me to start one?" },
  { role: "user", text: "What integrations do you support?" },
  { role: "bot", text: "WordPress, Shopify, Webflow, and any custom site via a single script tag. Zapier and webhooks too." },
];

export function LiveChatDemo() {
  const [visibleCount, setVisibleCount] = useState(1);
  const [typedLength, setTypedLength] = useState(0);

  useEffect(() => {
    const current = script[visibleCount - 1];
    if (!current) return;

    if (typedLength < current.text.length) {
      const timeout = setTimeout(() => setTypedLength((n) => n + 1), 18);
      return () => clearTimeout(timeout);
    }

    const pause = setTimeout(() => {
      if (visibleCount < script.length) {
        setVisibleCount((n) => n + 1);
        setTypedLength(0);
      } else {
        setVisibleCount(1);
        setTypedLength(0);
      }
    }, 1400);
    return () => clearTimeout(pause);
  }, [visibleCount, typedLength]);

  return (
    <div className="relative mx-auto w-full max-w-md">
      <div className="animate-float rounded-3xl border border-border bg-surface shadow-floating">
        <div className="flex items-center gap-3 border-b border-border p-4">
          <span className="flex size-9 items-center justify-center rounded-full bg-primary text-primary-foreground">
            <Bot className="size-4.5" />
          </span>
          <div>
            <p className="text-sm font-semibold">Support Assistant</p>
            <p className="flex items-center gap-1 text-xs text-success">
              <span className="size-1.5 rounded-full bg-success" /> Online
            </p>
          </div>
          <span className="ml-auto inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-[10px] font-semibold text-primary">
            <Sparkles className="size-3" /> Live preview
          </span>
        </div>
        <div className="flex h-72 flex-col gap-3 overflow-hidden p-4">
          {script.slice(0, visibleCount).map((message, index) => {
            const isLast = index === visibleCount - 1;
            const text = isLast ? message.text.slice(0, typedLength) : message.text;
            return (
              <div key={index} className={message.role === "user" ? "flex justify-end" : "flex justify-start"}>
                <div
                  className={
                    message.role === "user"
                      ? "max-w-[80%] rounded-2xl rounded-br-sm bg-primary px-3.5 py-2 text-sm text-primary-foreground"
                      : "max-w-[80%] rounded-2xl rounded-bl-sm bg-muted px-3.5 py-2 text-sm"
                  }
                >
                  {text}
                  {isLast && typedLength < message.text.length && <span className="animate-pulse">&nbsp;&#9612;</span>}
                </div>
              </div>
            );
          })}
        </div>
        <div className="flex items-center gap-2 border-t border-border p-3">
          <div className="flex-1 rounded-full border border-border bg-muted px-3.5 py-2 text-sm text-muted-foreground">
            Ask a question&hellip;
          </div>
          <span className="flex size-9 items-center justify-center rounded-full bg-primary text-primary-foreground">
            <Send className="size-4" />
          </span>
        </div>
      </div>
    </div>
  );
}
