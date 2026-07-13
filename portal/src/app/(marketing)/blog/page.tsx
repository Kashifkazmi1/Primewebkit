import type { Metadata } from "next";
import Link from "next/link";
import { PageHeader } from "@/components/marketing/page-header";
import { Reveal } from "@/components/marketing/reveal";
import { blogPosts } from "@/lib/content/blog";
import { formatDate } from "@/lib/utils";

export const metadata: Metadata = {
  title: "Blog",
  description: "Notes on chatbots, support automation, and retrieval-augmented generation from the PrimeWebKit team.",
  alternates: { canonical: "/blog" },
};

export default function BlogIndexPage() {
  return (
    <>
      <PageHeader eyebrow="Blog" title="Notes on support, chatbots, and RAG" />
      <section className="container-page py-20">
        <div className="mx-auto max-w-3xl space-y-6">
          {blogPosts.map((post, index) => (
            <Reveal key={post.slug} delay={index * 0.05}>
              <Link
                href={`/blog/${post.slug}`}
                className="block rounded-2xl border border-border bg-surface p-6 transition-colors hover:border-border-strong hover:shadow-elevated"
              >
                <p className="text-xs font-medium text-muted-foreground">
                  {formatDate(post.date)} &middot; {post.readingTime}
                </p>
                <h2 className="mt-2 font-display text-xl font-semibold">{post.title}</h2>
                <p className="mt-2 text-sm text-muted-foreground">{post.excerpt}</p>
              </Link>
            </Reveal>
          ))}
        </div>
      </section>
    </>
  );
}
