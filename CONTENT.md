# Adding content: blog posts, pillar pages, FAQs

All marketing content lives as typed data in `src/lib/content/`, rendered
by shared page templates — there's no CMS or database involved. Adding
content means adding an entry to one of these files; the page, its
metadata, and its structured data are generated from it automatically.

## Adding a blog post

Edit `src/lib/content/blog.ts` and add an entry to the `blogPosts` array:

```ts
{
  slug: "your-post-slug",              // becomes /blog/your-post-slug
  title: "Your post title",
  excerpt: "One or two sentences shown on the blog index card.",
  tag: "Support",                       // shown as a badge; group related posts under the same tag
  date: "2026-08-01",                   // ISO date, used for display and JSON-LD
  readingTime: "6 min read",
  relatedPillar: "ai-customer-support-agent", // slug from pillars.ts — linked at the end of the post
  body: [
    "First paragraph...",
    "Second paragraph...",
    // one string per paragraph
  ],
},
```

That's the whole change. `/blog` picks it up on the index grid, and
`/blog/your-post-slug` is statically generated at build time
(`generateStaticParams` in `src/app/(marketing)/blog/[slug]/page.tsx`)
with `BlogPosting` + `BreadcrumbList` JSON-LD, an OG/Twitter card, and
related-post links already wired up.

**Guidelines for a good post:**

- Aim for 800+ words (6–8 substantial paragraphs) — short posts read as
  thin content to both readers and search engines.
- Set `relatedPillar` to whichever of the three pillar pages
  (`src/lib/content/pillars.ts`) the post supports, and it'll be
  cross-linked automatically at the end of the article. Consider also
  adding the new post's slug to that pillar's `relatedPosts` array so the
  pillar links back down to it — that two-way link is what makes it a
  topic cluster rather than an isolated page.
- Reuse an existing `tag` where the topic fits, so the post surfaces in
  "More from the blog" alongside related posts on that tag.

## Adding a pillar page

Pillar pages are the three cornerstone SEO pages
(`/ai-chatbot-for-lead-generation`, `/ai-customer-support-agent`,
`/add-ai-chatbot-to-website`). Adding a fourth one:

1. Add an entry to the `pillars` array in `src/lib/content/pillars.ts`
   following the `Pillar` interface — `sections` is an ordered list of
   `{ id, heading, paragraphs, subsections? }`, each rendered as an `<h2>`
   with an anchor the on-page table of contents links to.
2. Create `src/app/(marketing)/<your-slug>/page.tsx`:

   ```tsx
   import type { Metadata } from "next";
   import { PillarPage } from "@/components/marketing/pillar-page";
   import { getPillar } from "@/lib/content/pillars";

   const pillar = getPillar("your-slug")!;

   export const metadata: Metadata = {
     title: pillar.title,
     description: pillar.metaDescription,
     alternates: { canonical: `/${pillar.slug}` },
     openGraph: { title: pillar.title, description: pillar.metaDescription, type: "article" },
   };

   export default function YourSlugPage() {
     return <PillarPage pillar={pillar} />;
   }
   ```

3. Add the route to `src/app/sitemap.ts` and, if it should be discoverable
   from navigation, to `src/components/marketing/site-header.tsx` and
   `site-footer.tsx`.

`PillarPage` handles the sticky table of contents, mid-page CTA, FAQ
accordion (with `FAQPage` JSON-LD), `Article` + `BreadcrumbList` JSON-LD,
and the related-pillars/related-posts footer automatically from the data
you provide — aim for 800–1500+ words across all sections combined.

## Adding an FAQ

Edit `src/lib/content/faqs.ts` — the array feeds both the homepage FAQ
accordion and `/docs/faq`, and both render `FAQPage` JSON-LD from the same
list, so one edit updates everywhere it appears.

## Docs pages

The docs sidebar and prev/next order is a single array —
`docsNav` in `src/components/docs/docs-shell.tsx`. Adding a new docs page
means creating `src/app/(marketing)/docs/<slug>/page.tsx` (wrap its content
in `<DocsShell active="/docs/<slug>">`) and adding `{ href, label }` to
`docsNav` in the position you want it to appear.
