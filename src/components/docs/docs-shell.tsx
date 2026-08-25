import { ArrowLeft, ArrowRight } from "lucide-react";
import Link from "next/link";
import type { ReactNode } from "react";
import { cn } from "@/lib/utils";

export const docsNav = [
  { href: "/docs", label: "Overview" },
  { href: "/docs/install", label: "Install the widget" },
  { href: "/docs/lead-capture", label: "Lead capture" },
  { href: "/docs/faq", label: "FAQ" },
  { href: "/docs/troubleshooting", label: "Troubleshooting" },
];

export function DocsShell({ active, children }: { active: string; children: ReactNode }) {
  const index = docsNav.findIndex((item) => item.href === active);
  const prev = index > 0 ? docsNav[index - 1] : null;
  const next = index >= 0 && index < docsNav.length - 1 ? docsNav[index + 1] : null;

  return (
    <section className="container-page py-16">
      <div className="mx-auto grid max-w-5xl gap-10 lg:grid-cols-[220px_1fr]">
        <nav aria-label="Documentation sections" className="lg:sticky lg:top-24 lg:h-fit">
          <p className="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Documentation</p>
          <ul className="mt-3 space-y-1">
            {docsNav.map((item) => (
              <li key={item.href}>
                <Link
                  href={item.href}
                  aria-current={item.href === active ? "page" : undefined}
                  className={cn(
                    "block rounded-lg px-3 py-2 text-sm font-medium transition-colors",
                    item.href === active
                      ? "bg-primary/10 text-primary"
                      : "text-muted-foreground hover:bg-muted hover:text-foreground",
                  )}
                >
                  {item.label}
                </Link>
              </li>
            ))}
          </ul>
        </nav>

        <div className="min-w-0 space-y-10">
          {children}
          <div className="flex items-center justify-between gap-4 border-t border-border pt-6">
            {prev ? (
              <Link
                href={prev.href}
                className="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-foreground"
              >
                <ArrowLeft className="size-4" /> {prev.label}
              </Link>
            ) : (
              <span />
            )}
            {next && (
              <Link href={next.href} className="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline">
                {next.label} <ArrowRight className="size-4" />
              </Link>
            )}
          </div>
        </div>
      </div>
    </section>
  );
}
