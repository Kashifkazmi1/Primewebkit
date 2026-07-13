"use client";

import { useRouter } from "next/navigation";
import { useEffect } from "react";
import { Header } from "@/components/dashboard/header";
import { Sidebar } from "@/components/dashboard/sidebar";
import { useAuth } from "@/lib/auth/auth-context";

export default function DashboardLayout({ children }: { children: React.ReactNode }) {
  const { user, isLoading } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (!isLoading && !user) {
      const redirect = encodeURIComponent(window.location.pathname);
      router.replace(`/login?redirect=${redirect}`);
    }
  }, [isLoading, user, router]);

  if (isLoading || !user) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <div className="size-8 animate-spin rounded-full border-2 border-primary border-t-transparent" aria-label="Loading" />
      </div>
    );
  }

  return (
    <div className="grid min-h-screen lg:grid-cols-[16rem_1fr]">
      <aside className="hidden border-r border-border lg:block">
        <div className="sticky top-0 h-screen">
          <Sidebar />
        </div>
      </aside>
      <div className="flex min-w-0 flex-col">
        <Header />
        <main className="flex-1 p-4 sm:p-6 lg:p-8">{children}</main>
      </div>
    </div>
  );
}
