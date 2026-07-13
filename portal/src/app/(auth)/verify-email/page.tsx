"use client";

import { Suspense, useEffect, useState } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { CheckCircle2, Loader2, XCircle } from "lucide-react";
import { ApiError } from "@/lib/api/client";
import { authApi } from "@/lib/api/endpoints";

type Status = "verifying" | "success" | "error";

export default function VerifyEmailPage() {
  return (
    <Suspense fallback={null}>
      <VerifyEmailContent />
    </Suspense>
  );
}

function VerifyEmailContent() {
  const searchParams = useSearchParams();
  const token = searchParams.get("token") ?? "";
  const [status, setStatus] = useState<Status>("verifying");
  const [message, setMessage] = useState<string>("");

  useEffect(() => {
    if (!token) {
      setStatus("error");
      setMessage("This verification link is missing its token.");
      return;
    }

    let cancelled = false;
    authApi
      .verifyEmail(token)
      .then(() => {
        if (!cancelled) setStatus("success");
      })
      .catch((error) => {
        if (cancelled) return;
        setStatus("error");
        setMessage(error instanceof ApiError ? error.message : "This verification link is invalid or has expired.");
      });
    return () => {
      cancelled = true;
    };
  }, [token]);

  return (
    <div className="animate-fade-up space-y-4 text-center">
      {status === "verifying" && (
        <>
          <Loader2 className="mx-auto size-8 animate-spin text-primary" />
          <h1 className="font-display text-2xl font-semibold tracking-tight">Verifying your email&hellip;</h1>
        </>
      )}
      {status === "success" && (
        <>
          <CheckCircle2 className="mx-auto size-10 text-success" />
          <h1 className="font-display text-2xl font-semibold tracking-tight">Email verified</h1>
          <p className="text-sm text-muted-foreground">Your address has been confirmed. You&apos;re all set.</p>
          <Link href="/dashboard" className="inline-block text-sm font-medium text-primary hover:underline">
            Go to dashboard
          </Link>
        </>
      )}
      {status === "error" && (
        <>
          <XCircle className="mx-auto size-10 text-danger" />
          <h1 className="font-display text-2xl font-semibold tracking-tight">Verification failed</h1>
          <p className="text-sm text-muted-foreground">{message}</p>
          <Link href="/login" className="inline-block text-sm font-medium text-primary hover:underline">
            Back to sign in
          </Link>
        </>
      )}
    </div>
  );
}
