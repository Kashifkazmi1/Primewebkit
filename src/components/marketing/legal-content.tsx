export function LegalContent({ children }: { children: React.ReactNode }) {
  return (
    <section className="container-page py-16">
      <div className="prose prose-slate mx-auto max-w-3xl dark:prose-invert prose-headings:font-display prose-a:text-primary">
        {children}
      </div>
    </section>
  );
}
