"use client";

import * as DialogPrimitive from "@radix-ui/react-dialog";
import { MessageCircle, ChevronDown, Menu, X } from "lucide-react";
import Link from "next/link";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { ThemeToggle } from "@/components/theme-toggle";
import { useAuth } from "@/lib/auth/auth-context";

const productLinks = [
  { href: "/features", label: "Features", description: "Everything your chatbot can do" },
  { href: "/templates", label: "Templates", description: "Pre-built chatbots for common use cases" },
  { href: "/integrations", label: "Integrations", description: "Connect the tools you already use" },
  { href: "/use-cases", label: "Use cases", description: "Support, sales, and lead generation" },
];

const resourceLinks = [
  { href: "/docs", label: "Documentation" },
  { href: "/api", label: "API reference" },
  { href: "/blog", label: "Blog" },
  { href: "/help", label: "Help center" },
  { href: "/changelog", label: "Changelog" },
];

const navLinks = [
  { href: "/pricing", label: "Pricing" },
  { href: "/industries", label: "Industries" },
  { href: "/contact", label: "Contact" },
];

export function SiteHeader() {
  const { user } = useAuth();
  const [mobileOpen, setMobileOpen] = useState(false);

  return (
    <header className="sticky top-0 z-40 border-b border-border bg-background/80 backdrop-blur-md">
      <div className="container-page flex h-16 items-center justify-between">
        <Link href="/" className="flex items-center gap-2 font-display text-lg font-semibold">
          <span className="flex size-8 items-center justify-center rounded-xl bg-primary text-primary-foreground">
            <MessageCircle className="size-4.5" />
          </span>
          PrimeWebKit
        </Link>

        <nav className="hidden items-center gap-1 lg:flex" aria-label="Main">
          <DropdownMenu>
            <DropdownMenuTrigger className="flex items-center gap-1 rounded-full px-3.5 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
              Product <ChevronDown className="size-3.5" />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" className="w-72">
              {productLinks.map((link) => (
                <DropdownMenuItem key={link.href} asChild>
                  <Link href={link.href} className="flex-col items-start gap-0.5 py-2.5">
                    <span className="font-medium">{link.label}</span>
                    <span className="text-xs text-muted-foreground">{link.description}</span>
                  </Link>
                </DropdownMenuItem>
              ))}
            </DropdownMenuContent>
          </DropdownMenu>
          <DropdownMenu>
            <DropdownMenuTrigger className="flex items-center gap-1 rounded-full px-3.5 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
              Resources <ChevronDown className="size-3.5" />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start">
              {resourceLinks.map((link) => (
                <DropdownMenuItem key={link.href} asChild>
                  <Link href={link.href}>{link.label}</Link>
                </DropdownMenuItem>
              ))}
            </DropdownMenuContent>
          </DropdownMenu>
          {navLinks.map((link) => (
            <Link
              key={link.href}
              href={link.href}
              className="rounded-full px-3.5 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
            >
              {link.label}
            </Link>
          ))}
        </nav>

        <div className="hidden items-center gap-2 lg:flex">
          <ThemeToggle />
          {user ? (
            <Button asChild>
              <Link href="/dashboard">Dashboard</Link>
            </Button>
          ) : (
            <>
              <Button asChild variant="ghost">
                <Link href="/login">Sign in</Link>
              </Button>
              <Button asChild>
                <Link href="/register">Start for free</Link>
              </Button>
            </>
          )}
        </div>

        <DialogPrimitive.Root open={mobileOpen} onOpenChange={setMobileOpen}>
          <DialogPrimitive.Trigger asChild>
            <Button variant="ghost" size="icon" className="lg:hidden" aria-label="Open menu">
              <Menu className="size-5" />
            </Button>
          </DialogPrimitive.Trigger>
          <DialogPrimitive.Portal>
            <DialogPrimitive.Overlay className="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden" />
            <DialogPrimitive.Content className="fixed inset-y-0 right-0 z-40 flex w-full max-w-xs flex-col gap-6 border-l border-border bg-surface p-6 lg:hidden">
              <div className="flex items-center justify-between">
                <DialogPrimitive.Title className="font-display font-semibold">Menu</DialogPrimitive.Title>
                <DialogPrimitive.Close asChild>
                  <Button variant="ghost" size="icon" aria-label="Close menu">
                    <X className="size-5" />
                  </Button>
                </DialogPrimitive.Close>
              </div>
              <nav className="flex flex-1 flex-col gap-1 overflow-y-auto">
                {[...productLinks, ...resourceLinks, ...navLinks].map((link) => (
                  <Link
                    key={link.href}
                    href={link.href}
                    onClick={() => setMobileOpen(false)}
                    className="rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-muted"
                  >
                    {link.label}
                  </Link>
                ))}
              </nav>
              <div className="flex flex-col gap-2">
                {user ? (
                  <Button asChild>
                    <Link href="/dashboard">Dashboard</Link>
                  </Button>
                ) : (
                  <>
                    <Button asChild variant="outline">
                      <Link href="/login">Sign in</Link>
                    </Button>
                    <Button asChild>
                      <Link href="/register">Start for free</Link>
                    </Button>
                  </>
                )}
              </div>
            </DialogPrimitive.Content>
          </DialogPrimitive.Portal>
        </DialogPrimitive.Root>
      </div>
    </header>
  );
}
