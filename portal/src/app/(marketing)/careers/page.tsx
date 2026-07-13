import type { Metadata } from "next";
import { PageHeader } from "@/components/marketing/page-header";
import { Reveal } from "@/components/marketing/reveal";

export const metadata: Metadata = {
  title: "Careers",
  description: "There are no open roles at PrimeWebKit right now — check back, or reach out directly.",
  alternates: { canonical: "/careers" },
};

export default function CareersPage() {
  return (
    <>
      <PageHeader eyebrow="Careers" title="No open roles right now" />
      <section className="container-page py-20">
        <Reveal className="mx-auto max-w-xl text-center text-sm text-muted-foreground">
          <p>
            We don&apos;t have open positions at the moment. If that changes, we&apos;ll post here first — in the
            meantime, feel free to introduce yourself at{" "}
            <a href="mailto:careers@primewebkit.com" className="font-medium text-primary hover:underline">
              careers@primewebkit.com
            </a>
            .
          </p>
        </Reveal>
      </section>
    </>
  );
}
