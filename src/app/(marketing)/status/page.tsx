import { CheckCircle2 } from "lucide-react";
import type { Metadata } from "next";
import { PageHeader } from "@/components/marketing/page-header";
import { Reveal } from "@/components/marketing/reveal";
import { Card, CardContent } from "@/components/ui/card";

export const metadata: Metadata = {
  title: "System Status",
  description: "Current operational status of PrimeWebKit services.",
  alternates: { canonical: "/status" },
};

const services = ["API", "Dashboard", "Chat widget", "AI generation", "Webhook delivery", "Website crawler"];

export default function StatusPage() {
  return (
    <>
      <PageHeader eyebrow="Status" title="System status" description="Real-time status of PrimeWebKit's core services." />
      <section className="container-page py-20">
        <Reveal className="mx-auto max-w-2xl rounded-2xl border border-success/30 bg-success/5 p-5 text-center">
          <p className="flex items-center justify-center gap-2 font-display font-semibold text-success">
            <CheckCircle2 className="size-5" /> All systems operational
          </p>
        </Reveal>
        <div className="mx-auto mt-8 max-w-2xl space-y-3">
          {services.map((service, index) => (
            <Reveal key={service} delay={index * 0.03}>
              <Card>
                <CardContent className="flex items-center justify-between p-4">
                  <span className="text-sm font-medium">{service}</span>
                  <span className="flex items-center gap-1.5 text-xs font-medium text-success">
                    <span className="size-1.5 rounded-full bg-success" /> Operational
                  </span>
                </CardContent>
              </Card>
            </Reveal>
          ))}
        </div>
        <p className="mx-auto mt-8 max-w-2xl text-center text-xs text-muted-foreground">
          This page reflects manually-reported status. Subscribe to updates at{" "}
          <a href="mailto:status@primewebkit.com" className="text-primary hover:underline">
            status@primewebkit.com
          </a>
          .
        </p>
      </section>
    </>
  );
}
