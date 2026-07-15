"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { Suspense } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { FieldError, Input, Label } from "@/components/ui/input";
import { GoogleSignInButton } from "@/components/auth/google-sign-in-button";
import { ApiError } from "@/lib/api/client";
import { useAuth } from "@/lib/auth/auth-context";
import { loginSchema, type LoginValues } from "@/lib/validation/auth";

export default function LoginPage() {
  return (
    <Suspense fallback={null}>
      <LoginForm />
    </Suspense>
  );
}

function LoginForm() {
  const { login, loginWithGoogle } = useAuth();
  const router = useRouter();
  const searchParams = useSearchParams();
  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<LoginValues>({ resolver: zodResolver(loginSchema) });

  const redirectTo = searchParams.get("redirect") || "/dashboard";

  async function onSubmit(values: LoginValues) {
    try {
      await login(values.email, values.password);
      router.push(redirectTo);
    } catch (error) {
      if (error instanceof ApiError) {
        toast.error(error.message);
        if (error.errors.email) setError("email", { message: error.errors.email[0] });
      } else {
        toast.error("Something went wrong. Please try again.");
      }
    }
  }

  async function onGoogleCredential(credential: string) {
    try {
      await loginWithGoogle(credential);
      router.push(redirectTo);
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Google sign-in failed. Please try again.");
    }
  }

  return (
    <div className="animate-fade-up space-y-8">
      <div className="space-y-2">
        <h1 className="font-display text-2xl font-semibold tracking-tight">Welcome back</h1>
        <p className="text-sm text-muted-foreground">Sign in to manage your chatbots and account.</p>
      </div>

      <GoogleSignInButton onCredential={onGoogleCredential} disabled={isSubmitting} />

      <div className="flex items-center gap-3 text-xs text-muted-foreground">
        <div className="h-px flex-1 bg-border" />
        or continue with email
        <div className="h-px flex-1 bg-border" />
      </div>

      <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-5">
        <div className="space-y-1.5">
          <Label htmlFor="email">Email</Label>
          <Input id="email" type="email" autoComplete="email" placeholder="you@company.com" {...register("email")} />
          <FieldError message={errors.email?.message} />
        </div>
        <div className="space-y-1.5">
          <div className="flex items-center justify-between">
            <Label htmlFor="password">Password</Label>
            <Link href="/forgot-password" className="text-xs font-medium text-primary hover:underline">
              Forgot password?
            </Link>
          </div>
          <Input id="password" type="password" autoComplete="current-password" {...register("password")} />
          <FieldError message={errors.password?.message} />
        </div>
        <Button type="submit" className="w-full" isLoading={isSubmitting}>
          Sign in
        </Button>
      </form>

      <p className="text-center text-sm text-muted-foreground">
        Don&apos;t have an account?{" "}
        <Link href="/register" className="font-medium text-primary hover:underline">
          Create one for free
        </Link>
      </p>
    </div>
  );
}
