"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { FieldError, Input, Label } from "@/components/ui/input";
import { GoogleSignInButton } from "@/components/auth/google-sign-in-button";
import { ApiError } from "@/lib/api/client";
import { useAuth } from "@/lib/auth/auth-context";
import { registerSchema, type RegisterValues } from "@/lib/validation/auth";

export default function RegisterPage() {
  const { register: registerUser, loginWithGoogle } = useAuth();
  const router = useRouter();
  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<RegisterValues>({ resolver: zodResolver(registerSchema) });

  async function onSubmit(values: RegisterValues) {
    try {
      await registerUser(values.name, values.email, values.password);
      toast.success("Account created — check your email to verify your address.");
      router.push("/dashboard");
    } catch (error) {
      if (error instanceof ApiError) {
        toast.error(error.message);
        Object.entries(error.errors).forEach(([field, messages]) => {
          if (field in values) setError(field as keyof RegisterValues, { message: messages[0] });
        });
      } else {
        toast.error("Something went wrong. Please try again.");
      }
    }
  }

  async function onGoogleCredential(credential: string) {
    try {
      await loginWithGoogle(credential);
      router.push("/dashboard");
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Google sign-in failed. Please try again.");
    }
  }

  return (
    <div className="animate-fade-up space-y-8">
      <div className="space-y-2">
        <h1 className="font-display text-2xl font-semibold tracking-tight">Create your account</h1>
        <p className="text-sm text-muted-foreground">Start building your AI chatbot — free, no credit card required.</p>
      </div>

      <GoogleSignInButton onCredential={onGoogleCredential} disabled={isSubmitting} />

      <div className="flex items-center gap-3 text-xs text-muted-foreground">
        <div className="h-px flex-1 bg-border" />
        or continue with email
        <div className="h-px flex-1 bg-border" />
      </div>

      <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-5">
        <div className="space-y-1.5">
          <Label htmlFor="name">Full name</Label>
          <Input id="name" autoComplete="name" placeholder="Jane Doe" {...register("name")} />
          <FieldError message={errors.name?.message} />
        </div>
        <div className="space-y-1.5">
          <Label htmlFor="email">Email</Label>
          <Input id="email" type="email" autoComplete="email" placeholder="you@company.com" {...register("email")} />
          <FieldError message={errors.email?.message} />
        </div>
        <div className="space-y-1.5">
          <Label htmlFor="password">Password</Label>
          <Input id="password" type="password" autoComplete="new-password" {...register("password")} />
          <FieldError message={errors.password?.message} />
        </div>
        <div className="space-y-1.5">
          <Label htmlFor="password_confirmation">Confirm password</Label>
          <Input id="password_confirmation" type="password" autoComplete="new-password" {...register("password_confirmation")} />
          <FieldError message={errors.password_confirmation?.message} />
        </div>
        <Button type="submit" className="w-full" isLoading={isSubmitting}>
          Create account
        </Button>
      </form>

      <p className="text-center text-xs text-muted-foreground">
        By creating an account, you agree to our{" "}
        <Link href="/terms" className="underline hover:text-foreground">
          Terms
        </Link>{" "}
        and{" "}
        <Link href="/privacy" className="underline hover:text-foreground">
          Privacy Policy
        </Link>
        .
      </p>

      <p className="text-center text-sm text-muted-foreground">
        Already have an account?{" "}
        <Link href="/login" className="font-medium text-primary hover:underline">
          Sign in
        </Link>
      </p>
    </div>
  );
}
