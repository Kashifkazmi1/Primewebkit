import Link from "next/link";
import { MessageCircle, Sparkles } from "lucide-react";
import { ThemeToggle } from "@/components/theme-toggle";

export default function AuthLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="grid min-h-screen lg:grid-cols-2">
      <div className="flex flex-col justify-between p-6 sm:p-10">
        <div className="flex items-center justify-between">
          <Link href="/" className="flex items-center gap-2 font-display text-lg font-semibold">
            <span className="flex size-8 items-center justify-center rounded-xl bg-primary text-primary-foreground">
              <MessageCircle className="size-4.5" />
            </span>
            PrimeWebKit
          </Link>
          <ThemeToggle />
        </div>
        <div className="mx-auto w-full max-w-sm py-12">{children}</div>
        <p className="text-center text-xs text-muted-foreground">
          &copy; {new Date().getFullYear()} PrimeWebKit. All rights reserved.
        </p>
      </div>
      <div className="relative hidden overflow-hidden border-l border-border bg-surface-2 lg:block">
        <div className="bg-grid bg-radial-fade absolute inset-0 opacity-40" />
        <div className="absolute -right-24 top-24 size-96 rounded-full bg-primary/20 blur-3xl" />
        <div className="absolute -left-16 bottom-16 size-72 rounded-full bg-accent/20 blur-3xl" />
        <div className="relative flex h-full flex-col items-center justify-center gap-8 p-16 text-center">
          <div className="animate-float rounded-3xl border border-border bg-surface p-6 shadow-floating">
            <div className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
              <Sparkles className="size-4 text-primary" />
              Trained on your content
            </div>
            <p className="mt-3 max-w-xs font-display text-xl font-semibold leading-snug">
              &ldquo;What&apos;s your refund policy?&rdquo;
            </p>
            <div className="mt-4 rounded-2xl bg-muted p-4 text-left text-sm text-muted-foreground">
              Refunds are available within 14 days of purchase — no questions asked. I can start that for you now if
              you&apos;d like.
            </div>
          </div>
          <div className="max-w-sm space-y-2">
            <h2 className="font-display text-2xl font-semibold tracking-tight">
              Ship a support agent that actually knows your product.
            </h2>
            <p className="text-sm text-muted-foreground">
              Crawl your site, upload docs, and go live with a trained AI chatbot in minutes.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
