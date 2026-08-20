"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { Suspense, useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { FieldError, Input, Label } from "@/components/ui/input";
import { ApiError } from "@/lib/api/client";
import { authApi } from "@/lib/api/endpoints";
import { resetPasswordSchema, type ResetPasswordValues } from "@/lib/validation/auth";

export default function ResetPasswordPage() {
  return (
    <Suspense fallback={null}>
      <ResetPasswordForm />
    </Suspense>
  );
}

function ResetPasswordForm() {
  const searchParams = useSearchParams();
  const token = searchParams.get("token") ?? "";
  const router = useRouter();
  const [done, setDone] = useState(false);
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<ResetPasswordValues>({ resolver: zodResolver(resetPasswordSchema) });

  async function onSubmit(values: ResetPasswordValues) {
    try {
      await authApi.resetPassword({ token, password: values.password, password_confirmation: values.password_confirmation });
      setDone(true);
      toast.success("Password reset — please sign in again.");
      setTimeout(() => router.push("/login"), 1500);
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "This reset link is invalid or has expired.");
    }
  }

  if (!token) {
    return (
      <div className="animate-fade-up space-y-4 text-center">
        <h1 className="font-display text-2xl font-semibold tracking-tight">Invalid link</h1>
        <p className="text-sm text-muted-foreground">This password reset link is missing its token.</p>
        <Link href="/forgot-password" className="inline-block text-sm font-medium text-primary hover:underline">
          Request a new link
        </Link>
      </div>
    );
  }

  if (done) {
    return (
      <div className="animate-fade-up space-y-4 text-center">
        <h1 className="font-display text-2xl font-semibold tracking-tight">Password updated</h1>
        <p className="text-sm text-muted-foreground">Redirecting you to sign in&hellip;</p>
      </div>
    );
  }

  return (
    <div className="animate-fade-up space-y-8">
      <div className="space-y-2">
        <h1 className="font-display text-2xl font-semibold tracking-tight">Choose a new password</h1>
        <p className="text-sm text-muted-foreground">Make it at least 8 characters.</p>
      </div>
      <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-5">
        <div className="space-y-1.5">
          <Label htmlFor="password">New password</Label>
          <Input id="password" type="password" autoComplete="new-password" {...register("password")} />
          <FieldError message={errors.password?.message} />
        </div>
        <div className="space-y-1.5">
          <Label htmlFor="password_confirmation">Confirm password</Label>
          <Input id="password_confirmation" type="password" autoComplete="new-password" {...register("password_confirmation")} />
          <FieldError message={errors.password_confirmation?.message} />
        </div>
        <Button type="submit" className="w-full" isLoading={isSubmitting}>
          Reset password
        </Button>
      </form>
    </div>
  );
}
