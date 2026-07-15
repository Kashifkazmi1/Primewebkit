export const pricingPlans = [
  {
    name: "Free",
    price: 0,
    tagline: "Try PrimeWebKit with no commitment",
    features: ["1 chatbot", "100 messages / month", "1 knowledge source", "Community support"],
    cta: "Start for free",
    highlighted: false,
  },
  {
    name: "Starter",
    price: 29,
    tagline: "For small teams launching their first bot",
    features: ["3 chatbots", "2,000 messages / month", "Unlimited knowledge sources", "Lead capture & webhooks", "Email support"],
    cta: "Start free trial",
    highlighted: true,
  },
  {
    name: "Growth",
    price: 99,
    tagline: "For teams scaling support across products",
    features: ["10 chatbots", "10,000 messages / month", "Team roles & permissions", "White-label branding", "Priority support"],
    cta: "Start free trial",
    highlighted: false,
  },
] as const;
