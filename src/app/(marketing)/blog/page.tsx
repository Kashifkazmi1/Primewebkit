import type { Metadata } from "next";
import Link from "next/link";
import { PageHeader } from "@/components/marketing/page-header";
import { Reveal } from "@/components/marketing/reveal";
import { Badge } from "@/components/ui/badge";
import { blogPosts } from "@/lib/content/blog";
import { formatDate } from "@/lib/utils";

export const metadata: Metadata = {
  title: "Blog",
  description: "Notes on chatbots, support automation, lead capture, and retrieval-augmented generation from the PrimeWebKit team.",
  alternates: { canonical: "/blog" },
};

export default function BlogIndexPage() {
  return (
    <>
      <PageHeader eyebrow="Blog" title="Notes on support, chatbots, and RAG" />
      <section className="container-page py-20">
        <div className="mx-auto grid max-w-5xl gap-5 sm:grid-cols-2">
          {blogPosts.map((post, index) => (
            <Reveal key={post.slug} delay={index * 0.04}>
              <Link
                href={`/blog/${post.slug}`}
                className="flex h-full flex-col rounded-2xl border border-border bg-surface p-6 transition-colors hover:border-border-strong hover:shadow-elevated"
              >
                <div className="flex items-center justify-between gap-3">
                  <Badge variant="outline">{post.tag}</Badge>
                  <p className="text-xs text-muted-foreground">{post.readingTime}</p>
                </div>
                <h2 className="mt-3 font-display text-lg font-semibold leading-snug">{post.title}</h2>
                <p className="mt-2 flex-1 text-sm text-muted-foreground">{post.excerpt}</p>
                <p className="mt-4 text-xs font-medium text-muted-foreground">{formatDate(post.date)}</p>
              </Link>
            </Reveal>
          ))}
        </div>
      </section>
    </>
  );
}
