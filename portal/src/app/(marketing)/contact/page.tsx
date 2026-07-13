"use client";

import { Mail, MessageCircle, ShieldAlert } from "lucide-react";
import { useState } from "react";
import { PageHeader } from "@/components/marketing/page-header";
import { Reveal } from "@/components/marketing/reveal";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input, Label, Textarea } from "@/components/ui/input";

const CONTACT_EMAIL = "hello@primewebkit.com";
const SECURITY_EMAIL = "security@primewebkit.com";

export default function ContactPage() {
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [message, setMessage] = useState("");

  const mailtoHref = `mailto:${CONTACT_EMAIL}?subject=${encodeURIComponent(
    `Message from ${name || "the PrimeWebKit site"}`,
  )}&body=${encodeURIComponent(`${message}\n\n— ${name} (${email})`)}`;

  return (
    <>
      <PageHeader
        eyebrow="Contact"
        title="Talk to the team"
        description="Questions about pricing, Enterprise plans, or anything else — we read every message."
      />
      <section className="container-page grid gap-8 py-20 lg:grid-cols-2">
        <Reveal>
          <Card>
            <CardContent className="space-y-5 p-6">
              <div className="space-y-1.5">
                <Label htmlFor="name">Name</Label>
                <Input id="name" value={name} onChange={(e) => setName(e.target.value)} />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="email">Email</Label>
                <Input id="email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="message">Message</Label>
                <Textarea id="message" rows={5} value={message} onChange={(e) => setMessage(e.target.value)} />
              </div>
              <Button asChild className="w-full" disabled={!message}>
                <a href={mailtoHref}>Send message</a>
              </Button>
              <p className="text-xs text-muted-foreground">
                This opens your email client addressed to {CONTACT_EMAIL} with your message pre-filled.
              </p>
            </CardContent>
          </Card>
        </Reveal>
        <Reveal delay={0.1} className="space-y-4">
          <ContactCard icon={Mail} title="Email" description={CONTACT_EMAIL} href={`mailto:${CONTACT_EMAIL}`} />
          <ContactCard icon={MessageCircle} title="Sales & Enterprise" description="Custom volume, SSO, and contracts" href={`mailto:${CONTACT_EMAIL}?subject=Enterprise%20inquiry`} />
          <ContactCard icon={ShieldAlert} title="Security disclosures" description={SECURITY_EMAIL} href={`mailto:${SECURITY_EMAIL}`} />
        </Reveal>
      </section>
    </>
  );
}

function ContactCard({
  icon: Icon,
  title,
  description,
  href,
}: {
  icon: React.ComponentType<{ className?: string }>;
  title: string;
  description: string;
  href: string;
}) {
  return (
    <a href={href} className="flex items-center gap-4 rounded-2xl border border-border bg-surface p-5 transition-colors hover:border-border-strong">
      <span className="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
        <Icon className="size-5" />
      </span>
      <div>
        <p className="font-display text-sm font-semibold">{title}</p>
        <p className="text-xs text-muted-foreground">{description}</p>
      </div>
    </a>
  );
}
