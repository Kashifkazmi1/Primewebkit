import Link from "next/link";

const LINK_PATTERN = /\[([^\]]+)\]\(([^)]+)\)/g;

// Blog/guide body copy is plain content strings, but supports a minimal
// inline-link syntax — [label](href) — so internal linking doesn't require
// a full markdown pipeline just for a handful of anchor tags per paragraph.
export function RichText({ text }: { text: string }) {
  LINK_PATTERN.lastIndex = 0;
  const parts: React.ReactNode[] = [];
  let lastIndex = 0;
  let match: RegExpExecArray | null;
  let key = 0;

  while ((match = LINK_PATTERN.exec(text)) !== null) {
    if (match.index > lastIndex) parts.push(text.slice(lastIndex, match.index));
    const [, label, href] = match;
    const isInternal = href.startsWith("/");
    parts.push(
      isInternal ? (
        <Link key={key++} href={href} className="font-medium text-primary hover:underline">
          {label}
        </Link>
      ) : (
        <a
          key={key++}
          href={href}
          target="_blank"
          rel="noopener noreferrer"
          className="font-medium text-primary hover:underline"
        >
          {label}
        </a>
      ),
    );
    lastIndex = LINK_PATTERN.lastIndex;
  }
  if (lastIndex < text.length) parts.push(text.slice(lastIndex));

  return <>{parts}</>;
}
