"use client";

import { Bot, Send } from "lucide-react";
import { use, useEffect, useRef, useState } from "react";
import { Button } from "@/components/ui/button";
import { ApiError } from "@/lib/api/client";
import { getFingerprint, getSessionId, widgetApi, type WidgetBotConfig, type WidgetMessage } from "@/lib/api/widget";

export default function PublicChatPage({ params }: { params: Promise<{ uuid: string }> }) {
  const { uuid } = use(params);
  const [bot, setBot] = useState<WidgetBotConfig | null>(null);
  const [messages, setMessages] = useState<WidgetMessage[]>([]);
  const [input, setInput] = useState("");
  const [sending, setSending] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const endRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    widgetApi
      .config(uuid)
      .then((res) => {
        setBot(res.bot);
        if (res.bot.welcome_message) {
          setMessages([
            { id: "welcome", role: "assistant", content: res.bot.welcome_message, created_at: new Date(0).toISOString() },
          ]);
        }
      })
      .catch((err) => setError(err instanceof ApiError ? err.message : "This chatbot is unavailable."));
  }, [uuid]);

  useEffect(() => {
    endRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  async function handleSend(e: React.FormEvent) {
    e.preventDefault();
    const text = input.trim();
    if (!text || sending) return;

    setInput("");
    setSending(true);
    setMessages((prev) => [
      ...prev,
      { id: `local-${Date.now()}`, role: "user", content: text, created_at: new Date().toISOString() },
    ]);

    try {
      const res = await widgetApi.sendMessage(uuid, {
        session_id: getSessionId(uuid),
        fingerprint: getFingerprint(),
        message: text,
      });
      setMessages((prev) => [...prev.slice(0, -1), res.user_message, res.message]);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Something went wrong. Please try again.");
    } finally {
      setSending(false);
    }
  }

  if (error && !bot) {
    return (
      <div className="flex min-h-screen items-center justify-center p-6 text-center">
        <p className="text-muted-foreground">{error}</p>
      </div>
    );
  }

  return (
    <div className="mx-auto flex min-h-screen max-w-2xl flex-col">
      <header className="flex items-center gap-3 border-b border-border p-4">
        <span
          className="flex size-9 items-center justify-center rounded-full text-white"
          style={{ backgroundColor: bot?.primary_color ?? "var(--color-primary)" }}
        >
          <Bot className="size-4.5" />
        </span>
        <p className="font-display font-semibold">{bot?.name ?? "Chat"}</p>
      </header>

      <div className="flex-1 space-y-3 overflow-y-auto p-4">
        {messages.map((message) => (
          <div key={message.id} className={message.role === "user" ? "flex justify-end" : "flex justify-start"}>
            <div
              className={
                message.role === "user"
                  ? "max-w-[80%] rounded-2xl rounded-br-sm bg-primary px-4 py-2.5 text-sm text-primary-foreground"
                  : "max-w-[80%] rounded-2xl rounded-bl-sm bg-muted px-4 py-2.5 text-sm"
              }
            >
              {message.content}
            </div>
          </div>
        ))}
        {sending && (
          <div className="flex justify-start">
            <div className="flex gap-1 rounded-2xl rounded-bl-sm bg-muted px-4 py-3">
              <span className="size-1.5 animate-pulse rounded-full bg-muted-foreground" />
              <span className="size-1.5 animate-pulse rounded-full bg-muted-foreground [animation-delay:150ms]" />
              <span className="size-1.5 animate-pulse rounded-full bg-muted-foreground [animation-delay:300ms]" />
            </div>
          </div>
        )}
        {error && <p className="text-center text-xs text-danger">{error}</p>}
        <div ref={endRef} />
      </div>

      <form onSubmit={handleSend} className="flex items-center gap-2 border-t border-border p-4">
        <input
          value={input}
          onChange={(e) => setInput(e.target.value)}
          placeholder="Ask a question…"
          className="h-11 flex-1 rounded-full border border-border bg-surface px-4 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          disabled={!bot || sending}
        />
        <Button type="submit" size="icon" disabled={!bot || sending || !input.trim()} aria-label="Send message">
          <Send className="size-4" />
        </Button>
      </form>
    </div>
  );
}
