"use client";

import { Check, Copy } from "lucide-react";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

export function CodeBlock({ code, className }: { code: string; className?: string }) {
  const [copied, setCopied] = useState(false);

  async function copy() {
    await navigator.clipboard.writeText(code);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  }

  return (
    <div className={cn("relative rounded-xl border border-border bg-muted p-4", className)}>
      <pre className="overflow-x-auto whitespace-pre-wrap break-all font-mono text-xs">{code}</pre>
      <Button
        variant="outline"
        size="icon"
        className="absolute right-3 top-3 bg-surface"
        onClick={copy}
        aria-label="Copy code"
      >
        {copied ? <Check className="size-4 text-success" /> : <Copy className="size-4" />}
      </Button>
    </div>
  );
}
