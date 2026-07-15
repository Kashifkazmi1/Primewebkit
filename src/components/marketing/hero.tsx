"use client";

import { motion } from "framer-motion";
import { ArrowRight, Sparkles } from "lucide-react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { LiveChatDemo } from "@/components/marketing/live-chat-demo";

export function Hero() {
  return (
    <section className="relative overflow-hidden pb-20 pt-16 sm:pt-24">
      <div className="bg-grid bg-radial-fade absolute inset-0 opacity-60" aria-hidden />
      <div className="absolute left-1/2 top-0 h-[36rem] w-[36rem] -translate-x-1/2 rounded-full bg-primary/15 blur-3xl" aria-hidden />

      <div className="container-page relative grid gap-12 lg:grid-cols-2 lg:items-center lg:gap-8">
        <div>
          <motion.div
            initial={{ opacity: 0, y: 16 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5 }}
            className="inline-flex items-center gap-2 rounded-full border border-border bg-surface px-3.5 py-1.5 text-xs font-medium shadow-elevated"
          >
            <Sparkles className="size-3.5 text-primary" />
            Trained on your content in minutes
          </motion.div>

          <motion.h1
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: 0.05 }}
            className="mt-6 font-display text-4xl font-semibold leading-[1.1] tracking-tight sm:text-5xl lg:text-6xl"
          >
            Turn your website into an <span className="text-gradient">AI support agent</span>
          </motion.h1>

          <motion.p
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: 0.1 }}
            className="mt-6 max-w-lg text-lg text-muted-foreground"
          >
            Crawl your site, upload documents, or write FAQs — PrimeWebKit builds a chatbot that answers customers
            accurately, captures leads, and streams responses in real time. Live in one script tag.
          </motion.p>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: 0.15 }}
            className="mt-8 flex flex-col gap-3 sm:flex-row"
          >
            <Button asChild size="lg">
              <Link href="/register">
                Start building for free <ArrowRight className="size-4" />
              </Link>
            </Button>
            <Button asChild size="lg" variant="outline">
              <Link href="/pricing">See pricing</Link>
            </Button>
          </motion.div>

          <motion.p
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ duration: 0.6, delay: 0.25 }}
            className="mt-5 text-xs text-muted-foreground"
          >
            No credit card required &middot; Free plan available &middot; Sign in with Google
          </motion.p>
        </div>

        <motion.div
          initial={{ opacity: 0, scale: 0.96 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ duration: 0.7, delay: 0.15 }}
        >
          <LiveChatDemo />
        </motion.div>
      </div>
    </section>
  );
}
