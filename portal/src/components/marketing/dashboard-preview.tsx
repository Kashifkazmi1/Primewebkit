import { BarChart3, Bot, MessageSquare, Users } from "lucide-react";
import { Reveal } from "@/components/marketing/reveal";

export function DashboardPreviewSection() {
  return (
    <section className="container-page py-24">
      <div className="grid items-center gap-12 lg:grid-cols-2">
        <Reveal>
          <p className="text-sm font-semibold uppercase tracking-widest text-primary">Analytics</p>
          <h2 className="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">
            See exactly what your customers are asking
          </h2>
          <p className="mt-4 text-muted-foreground">
            Track top questions, response times, satisfaction ratings, and lead conversion — all from a single
            dashboard that updates in real time as conversations happen.
          </p>
          <ul className="mt-6 space-y-3 text-sm">
            <li className="flex items-center gap-2">
              <span className="size-1.5 rounded-full bg-primary" /> Conversation transcripts with full context
            </li>
            <li className="flex items-center gap-2">
              <span className="size-1.5 rounded-full bg-primary" /> Lead capture synced to your CRM via webhooks
            </li>
            <li className="flex items-center gap-2">
              <span className="size-1.5 rounded-full bg-primary" /> Per-bot usage against your plan limits
            </li>
          </ul>
        </Reveal>

        <Reveal delay={0.1}>
          <div className="rounded-2xl border border-border bg-surface p-5 shadow-floating">
            <div className="grid grid-cols-3 gap-3">
              {[
                { icon: Bot, label: "Chatbots", value: "6" },
                { icon: MessageSquare, label: "Conversations", value: "1,204" },
                { icon: Users, label: "Leads captured", value: "312" },
              ].map((stat) => (
                <div key={stat.label} className="rounded-xl border border-border bg-surface-2 p-3">
                  <stat.icon className="size-4 text-primary" />
                  <p className="mt-2 font-display text-lg font-semibold">{stat.value}</p>
                  <p className="text-[11px] text-muted-foreground">{stat.label}</p>
                </div>
              ))}
            </div>
            <div className="mt-4 rounded-xl border border-border bg-surface-2 p-4">
              <div className="flex items-center justify-between">
                <p className="text-xs font-semibold text-muted-foreground">Conversations this week</p>
                <BarChart3 className="size-4 text-muted-foreground" />
              </div>
              <div className="mt-4 flex h-28 items-end gap-2">
                {[40, 65, 50, 80, 60, 95, 70].map((height, index) => (
                  <div key={index} className="flex-1 rounded-t-md bg-gradient-to-t from-primary to-accent" style={{ height: `${height}%` }} />
                ))}
              </div>
            </div>
          </div>
        </Reveal>
      </div>
    </section>
  );
}
