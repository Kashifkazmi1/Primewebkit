export const faqs = [
  {
    question: "How does PrimeWebKit train a chatbot on my content?",
    answer:
      "You crawl your website, upload documents (PDF, DOCX, TXT, MD, CSV), or write Q&A pairs. We chunk and embed that content, then retrieve the most relevant pieces for each visitor question — retrieval-augmented generation, so answers stay grounded in your actual content.",
  },
  {
    question: "Which AI model powers the chatbot?",
    answer: "PrimeWebKit uses Google Gemini for both chat generation and embeddings, with streaming responses.",
  },
  {
    question: "Can I use my own domain and remove PrimeWebKit branding?",
    answer: "Yes — white-label options are available on paid plans, including custom colors and removing our branding.",
  },
  {
    question: "How do webhooks work?",
    answer:
      "Register an endpoint URL and choose which events to receive — new leads, completed chats, subscription changes, and more. Each delivery is signed so you can verify it came from PrimeWebKit.",
  },
  {
    question: "Is there a free plan?",
    answer:
      "Yes, a free plan is available with no credit card required. Paid plans add volume, more bots, and white-label features.",
  },
  {
    question: "Can I invite my team?",
    answer: "Yes — invite teammates with owner, manager, or member roles to collaborate on shared chatbots.",
  },
] as const;
