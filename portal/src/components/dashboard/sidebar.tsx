"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { Bot, type LucideIcon } from "lucide-react";
import { adminNavItem, navItems } from "@/lib/nav-config";
import { cn } from "@/lib/utils";
import { useAuth } from "@/lib/auth/auth-context";

interface NavLinkProps {
  href: string;
  label: string;
  icon: LucideIcon;
  onNavigate?: () => void;
}

function NavLink({ href, label, icon: Icon, onNavigate }: NavLinkProps) {
  const pathname = usePathname();
  const active = href === "/dashboard" ? pathname === href : pathname.startsWith(href);

  return (
    <Link
      href={href}
      onClick={onNavigate}
      aria-current={active ? "page" : undefined}
      className={cn(
        "flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground",
        active && "bg-primary/10 text-primary hover:bg-primary/10 hover:text-primary",
      )}
    >
      <Icon className="size-4.5" aria-hidden />
      {label}
    </Link>
  );
}

export function Sidebar({ onNavigate }: { onNavigate?: () => void }) {
  const { user } = useAuth();
  const isAdmin = user?.role === "super-admin" || user?.role === "admin";

  return (
    <div className="flex h-full flex-col gap-6 p-4">
      <Link href="/dashboard" className="flex items-center gap-2 px-2 py-1 font-display text-lg font-semibold">
        <span className="flex size-8 items-center justify-center rounded-xl bg-primary text-primary-foreground">
          <Bot className="size-4.5" />
        </span>
        PrimeWebKit
      </Link>
      <nav className="flex flex-1 flex-col gap-1">
        {navItems.map((item) => (
          <NavLink key={item.href} {...item} onNavigate={onNavigate} />
        ))}
        {isAdmin && (
          <>
            <div className="my-2 h-px bg-border" />
            <NavLink {...adminNavItem} onNavigate={onNavigate} />
          </>
        )}
      </nav>
      <div className="rounded-2xl border border-border bg-surface-2 p-4">
        <p className="font-display text-sm font-semibold">Need a hand?</p>
        <p className="mt-1 text-xs text-muted-foreground">Docs, guides, and API reference.</p>
        <Link href="/docs" className="mt-3 inline-block text-xs font-semibold text-primary hover:underline">
          View documentation &rarr;
        </Link>
      </div>
    </div>
  );
}
