import type { Metadata } from "next";
import { Inter, JetBrains_Mono, Sora } from "next/font/google";
import { Toaster } from "sonner";
import { AuthProvider } from "@/lib/auth/auth-context";
import { ThemeProvider } from "@/components/theme-provider";
import { env } from "@/lib/env";
import "./globals.css";

const inter = Inter({ variable: "--font-inter", subsets: ["latin"], display: "swap" });
const sora = Sora({ variable: "--font-sora", subsets: ["latin"], display: "swap" });
const jetbrainsMono = JetBrains_Mono({ variable: "--font-jetbrains-mono", subsets: ["latin"], display: "swap" });

export const metadata: Metadata = {
  metadataBase: new URL(env.siteUrl),
  title: {
    default: "PrimeWebKit — AI Chatbots Trained on Your Content",
    template: "%s | PrimeWebKit",
  },
  description:
    "Build an AI chatbot trained on your website, docs, and FAQs in minutes. Streaming answers, lead capture, analytics, and a single script tag install — powered by PrimeWebKit.",
  applicationName: "PrimeWebKit",
  keywords: [
    "AI chatbot",
    "chatbot builder",
    "customer support AI",
    "RAG chatbot",
    "website chatbot widget",
    "AI customer service",
  ],
  authors: [{ name: "PrimeWebKit" }],
  creator: "PrimeWebKit",
  openGraph: {
    type: "website",
    siteName: "PrimeWebKit",
    url: env.siteUrl,
    title: "PrimeWebKit — AI Chatbots Trained on Your Content",
    description:
      "Build an AI chatbot trained on your website, docs, and FAQs in minutes. Streaming answers, lead capture, analytics, and a single script tag install.",
  },
  twitter: {
    card: "summary_large_image",
    title: "PrimeWebKit — AI Chatbots Trained on Your Content",
    description: "Build an AI chatbot trained on your website, docs, and FAQs in minutes.",
  },
  robots: {
    index: true,
    follow: true,
  },
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html
      lang="en"
      suppressHydrationWarning
      className={`${inter.variable} ${sora.variable} ${jetbrainsMono.variable} h-full antialiased`}
    >
      <body className="flex min-h-full flex-col bg-background font-sans text-foreground">
        <ThemeProvider attribute="class" defaultTheme="system" enableSystem disableTransitionOnChange>
          <AuthProvider>
            {children}
            <Toaster position="top-right" richColors closeButton />
          </AuthProvider>
        </ThemeProvider>
      </body>
    </html>
  );
}
