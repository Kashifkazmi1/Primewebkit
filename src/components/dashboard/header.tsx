"use client";

import * as DialogPrimitive from "@radix-ui/react-dialog";
import { LogOut, Menu, Settings, User as UserIcon } from "lucide-react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { NotificationsMenu } from "@/components/dashboard/notifications-menu";
import { Sidebar } from "@/components/dashboard/sidebar";
import { ThemeToggle } from "@/components/theme-toggle";
import { useAuth } from "@/lib/auth/auth-context";
import { initials } from "@/lib/utils";

export function Header() {
  const { user, logout } = useAuth();
  const router = useRouter();
  const [mobileOpen, setMobileOpen] = useState(false);

  async function handleLogout() {
    await logout();
    router.push("/login");
  }

  return (
    <header className="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-border bg-surface/80 px-4 backdrop-blur-md sm:px-6">
      <DialogPrimitive.Root open={mobileOpen} onOpenChange={setMobileOpen}>
        <DialogPrimitive.Trigger asChild>
          <Button variant="ghost" size="icon" className="lg:hidden" aria-label="Open navigation">
            <Menu className="size-5" />
          </Button>
        </DialogPrimitive.Trigger>
        <DialogPrimitive.Portal>
          <DialogPrimitive.Overlay className="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden" />
          <DialogPrimitive.Content className="fixed inset-y-0 left-0 z-40 w-72 border-r border-border bg-surface lg:hidden">
            <DialogPrimitive.Title className="sr-only">Navigation menu</DialogPrimitive.Title>
            <Sidebar onNavigate={() => setMobileOpen(false)} />
          </DialogPrimitive.Content>
        </DialogPrimitive.Portal>
      </DialogPrimitive.Root>

      <div className="flex-1" />

      <NotificationsMenu />
      <ThemeToggle />

      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <button className="flex items-center gap-2 rounded-full p-1 pr-2 transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            <Avatar>
              <AvatarFallback>{user ? initials(user.name) : "?"}</AvatarFallback>
            </Avatar>
            <span className="hidden text-sm font-medium sm:inline">{user?.name}</span>
          </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" className="w-56">
          <DropdownMenuLabel>
            <p className="truncate font-medium">{user?.name}</p>
            <p className="truncate text-xs font-normal text-muted-foreground">{user?.email}</p>
          </DropdownMenuLabel>
          <DropdownMenuSeparator />
          <DropdownMenuItem asChild>
            <Link href="/settings">
              <UserIcon className="size-4" /> Profile
            </Link>
          </DropdownMenuItem>
          <DropdownMenuItem asChild>
            <Link href="/settings">
              <Settings className="size-4" /> Settings
            </Link>
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem onSelect={handleLogout}>
            <LogOut className="size-4" /> Sign out
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </header>
  );
}
