import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { CtaSection } from "@/components/marketing/cta-section";
import { LegalContent } from "@/components/marketing/legal-content";
import { PageHeader } from "@/components/marketing/page-header";
import { Badge } from "@/components/ui/badge";
import { JsonLd } from "@/components/seo/json-ld";
import { breadcrumbSchema, blogPostingSchema } from "@/lib/seo/schema";
import { blogPosts } from "@/lib/content/blog";
import { getPillar } from "@/lib/content/pillars";
import { formatDate } from "@/lib/utils";

export function generateStaticParams() {
  return blogPosts.map((post) => ({ slug: post.slug }));
}

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }): Promise<Metadata> {
  const { slug } = await params;
  const post = blogPosts.find((p) => p.slug === slug);
  if (!post) return {};
  return {
    title: post.title,
    description: post.excerpt,
    alternates: { canonical: `/blog/${post.slug}` },
    openGraph: { title: post.title, description: post.excerpt, type: "article", publishedTime: post.date },
  };
}

export default async function BlogPostPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const post = blogPosts.find((p) => p.slug === slug);
  if (!post) notFound();

  const relatedPillar = getPillar(post.relatedPillar);
  const relatedPosts = blogPosts.filter((p) => p.slug !== post.slug && p.tag === post.tag).slice(0, 2);
  const morePosts = relatedPosts.length > 0 ? relatedPosts : blogPosts.filter((p) => p.slug !== post.slug).slice(0, 2);

  return (
    <>
      <JsonLd
        data={[
          breadcrumbSchema([
            { name: "Blog", url: "/blog" },
            { name: post.title, url: `/blog/${post.slug}` },
          ]),
          blogPostingSchema({
            headline: post.title,
            description: post.excerpt,
            url: `/blog/${post.slug}`,
            datePublished: post.date,
          }),
        ]}
      />
      <PageHeader
        eyebrow={`${post.tag} · ${formatDate(post.date)} · ${post.readingTime}`}
        title={post.title}
        description="By the PrimeWebKit team"
      />
      <LegalContent>
        {post.body.map((paragraph, index) => (
          <p key={index}>{paragraph}</p>
        ))}
        {relatedPillar && (
          <p>
            Want the full picture?{" "}
            <Link href={`/${relatedPillar.slug}`}>Read our guide on {relatedPillar.title.toLowerCase()}</Link>.
          </p>
        )}
      </LegalContent>

      <section className="container-page pb-4">
        <div className="mx-auto max-w-3xl border-t border-border pt-10">
          <p className="text-xs font-semibold uppercase tracking-widest text-muted-foreground">More from the blog</p>
          <div className="mt-4 grid gap-4 sm:grid-cols-2">
            {morePosts.map((related) => (
              <Link
                key={related.slug}
                href={`/blog/${related.slug}`}
                className="rounded-2xl border border-border bg-surface p-5 transition-colors hover:border-border-strong hover:shadow-elevated"
              >
                <Badge variant="outline">{related.tag}</Badge>
                <p className="mt-2 font-display text-sm font-semibold leading-snug">{related.title}</p>
              </Link>
            ))}
          </div>
        </div>
      </section>

      <CtaSection />
    </>
  );
}
