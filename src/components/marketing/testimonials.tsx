import { Star } from "lucide-react";
import { Reveal } from "@/components/marketing/reveal";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";

const testimonials = [
  {
    quote:
      "We trained a bot on our help docs in an afternoon. Support tickets for repeat questions dropped by half in the first month.",
    name: "Maya Chen",
    role: "Head of Support, Loopline",
  },
  {
    quote:
      "The webhook integration meant leads from chat conversations landed directly in our CRM. No manual export, no delay.",
    name: "Daniel Osei",
    role: "Growth Lead, Fenwick Studio",
  },
  {
    quote:
      "Streaming responses feel instant, and the white-label option meant our customers never saw a third-party logo.",
    name: "Priya Nair",
    role: "Founder, Nairobi Threads",
  },
];

export function Testimonials() {
  return (
    <section className="container-page py-24">
      <Reveal className="mx-auto max-w-2xl text-center">
        <p className="text-sm font-semibold uppercase tracking-widest text-primary">Loved by support teams</p>
        <h2 className="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">
          Teams ship faster with PrimeWebKit
        </h2>
      </Reveal>

      <div className="mt-14 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {testimonials.map((testimonial, index) => (
          <Reveal key={testimonial.name} delay={index * 0.08}>
            <figure className="flex h-full flex-col justify-between rounded-2xl border border-border bg-surface p-6 shadow-elevated">
              <div>
                <div className="flex gap-0.5 text-warning">
                  {Array.from({ length: 5 }).map((_, i) => (
                    <Star key={i} className="size-4 fill-current" />
                  ))}
                </div>
                <blockquote className="mt-4 text-sm leading-relaxed text-foreground">&ldquo;{testimonial.quote}&rdquo;</blockquote>
              </div>
              <figcaption className="mt-6 flex items-center gap-3">
                <Avatar>
                  <AvatarFallback>{testimonial.name.slice(0, 1)}</AvatarFallback>
                </Avatar>
                <div>
                  <p className="text-sm font-medium">{testimonial.name}</p>
                  <p className="text-xs text-muted-foreground">{testimonial.role}</p>
                </div>
              </figcaption>
            </figure>
          </Reveal>
        ))}
      </div>
    </section>
  );
}
