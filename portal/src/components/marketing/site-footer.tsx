import { MessageCircle } from "lucide-react";
import Link from "next/link";
import { FacebookMark, InstagramMark, LinkedinMark } from "@/components/icons/brand-icons";

const socialLinks = [
  { href: "https://www.facebook.com/people/Prime-Webkit/61578131876843/", label: "PrimeWebKit on Facebook", Icon: FacebookMark },
  { href: "https://www.linkedin.com/company/prime-webkit", label: "PrimeWebKit on LinkedIn", Icon: LinkedinMark },
];

const columns = [
  {
    title: "Product",
    links: [
      { href: "/features", label: "Features" },
      { href: "/pricing", label: "Pricing" },
      { href: "/templates", label: "Templates" },
      { href: "/integrations", label: "Integrations" },
      { href: "/use-cases", label: "Use cases" },
      { href: "/industries", label: "Industries" },
    ],
  },
  {
    title: "Resources",
    links: [
      { href: "/docs", label: "Documentation" },
      { href: "/api", label: "API reference" },
      { href: "/guides", label: "Guides" },
      { href: "/blog", label: "Blog" },
      { href: "/help", label: "Help center" },
      { href: "/changelog", label: "Changelog" },
      { href: "/roadmap", label: "Roadmap" },
    ],
  },
  {
    title: "Company",
    links: [
      { href: "/about", label: "About" },
      { href: "/careers", label: "Careers" },
      { href: "/contact", label: "Contact" },
      { href: "/status", label: "Status" },
      { href: "/security", label: "Security" },
    ],
  },
  {
    title: "Legal",
    links: [
      { href: "/privacy", label: "Privacy policy" },
      { href: "/terms", label: "Terms of service" },
      { href: "/cookies", label: "Cookie policy" },
    ],
  },
];

export function SiteFooter() {
  return (
    <footer className="border-t border-border bg-surface-2">
      <div className="container-page grid gap-10 py-16 sm:grid-cols-2 lg:grid-cols-6">
        <div className="sm:col-span-2 lg:col-span-2">
          <Link href="/" className="flex items-center gap-2 font-display text-lg font-semibold">
            <span className="flex size-8 items-center justify-center rounded-xl bg-primary text-primary-foreground">
              <MessageCircle className="size-4.5" />
            </span>
            PrimeWebKit
          </Link>
          <p className="mt-4 max-w-xs text-sm text-muted-foreground">
            AI chatbots trained on your own content — crawl your site, upload docs, and go live in minutes.
          </p>
          <div className="mt-5 flex items-center gap-3 text-muted-foreground">
            {socialLinks.map(({ href, label, Icon }) => (
              <a key={href} href={href} target="_blank" rel="noopener noreferrer" aria-label={label} className="hover:text-foreground">
                <Icon className="size-4.5" />
              </a>
            ))}
            <span
              aria-disabled="true"
              title="Instagram — coming soon"
              className="cursor-not-allowed opacity-40"
            >
              <InstagramMark className="size-4.5" />
            </span>
          </div>
        </div>
        {columns.map((column) => (
          <div key={column.title}>
            <p className="font-display text-sm font-semibold">{column.title}</p>
            <ul className="mt-4 space-y-2.5">
              {column.links.map((link) => (
                <li key={link.href}>
                  <Link href={link.href} className="text-sm text-muted-foreground hover:text-foreground">
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        ))}
      </div>
      <div className="container-page flex flex-col items-center justify-between gap-4 border-t border-border py-6 text-xs text-muted-foreground sm:flex-row">
        <p>&copy; {new Date().getFullYear()} PrimeWebKit. All rights reserved.</p>
        <p>Built for support teams who&apos;d rather ship than write FAQs by hand.</p>
      </div>
    </footer>
  );
}
