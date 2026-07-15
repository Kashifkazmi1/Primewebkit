import {
  Bot,
  CreditCard,
  Key,
  LayoutDashboard,
  Settings,
  Shield,
  Users,
  Webhook,
} from "lucide-react";

export const navItems = [
  { href: "/dashboard", label: "Overview", icon: LayoutDashboard },
  { href: "/bots", label: "Chatbots", icon: Bot },
  { href: "/webhooks", label: "Webhooks", icon: Webhook },
  { href: "/api-keys", label: "API Keys", icon: Key },
  { href: "/team", label: "Team", icon: Users },
  { href: "/billing", label: "Billing", icon: CreditCard },
  { href: "/settings", label: "Settings", icon: Settings },
] as const;

export const adminNavItem = { href: "/admin", label: "Admin", icon: Shield } as const;
