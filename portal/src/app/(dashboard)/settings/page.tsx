"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { FieldError, Input, Label } from "@/components/ui/input";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { ApiError } from "@/lib/api/client";
import { authApi } from "@/lib/api/endpoints";
import { useAuth } from "@/lib/auth/auth-context";
import { useRouter } from "next/navigation";

const profileSchema = z.object({ name: z.string().min(2).max(150) });
type ProfileValues = z.infer<typeof profileSchema>;

const passwordSchema = z
  .object({
    current_password: z.string().min(1, "Enter your current password"),
    new_password: z.string().min(8, "Password must be at least 8 characters long"),
    new_password_confirmation: z.string().min(1, "Please confirm your new password"),
  })
  .refine((data) => data.new_password === data.new_password_confirmation, {
    message: "Passwords do not match.",
    path: ["new_password_confirmation"],
  });
type PasswordValues = z.infer<typeof passwordSchema>;

export default function SettingsPage() {
  const { user, setUser, logout } = useAuth();
  const router = useRouter();
  const [deletePassword, setDeletePassword] = useState("");
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [deleting, setDeleting] = useState(false);

  const profileForm = useForm<ProfileValues>({
    resolver: zodResolver(profileSchema),
    defaultValues: { name: user?.name ?? "" },
  });
  const passwordForm = useForm<PasswordValues>({ resolver: zodResolver(passwordSchema) });

  async function onProfileSubmit(values: ProfileValues) {
    try {
      const updated = await authApi.updateProfile({ name: values.name });
      setUser(updated);
      toast.success("Profile updated.");
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not update your profile.");
    }
  }

  async function onPasswordSubmit(values: PasswordValues) {
    try {
      await authApi.changePassword({
        current_password: values.current_password,
        new_password: values.new_password,
        new_password_confirmation: values.new_password_confirmation,
      });
      passwordForm.reset();
      toast.success("Password changed.");
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not change your password.");
    }
  }

  async function handleDeleteAccount() {
    setDeleting(true);
    try {
      await authApi.deleteAccount(deletePassword);
      await logout();
      router.push("/");
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not delete your account.");
    } finally {
      setDeleting(false);
    }
  }

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      <div>
        <h1 className="font-display text-2xl font-semibold tracking-tight">Settings</h1>
        <p className="text-sm text-muted-foreground">Manage your profile, security, and account.</p>
      </div>

      <Card>
        <form onSubmit={profileForm.handleSubmit(onProfileSubmit)} noValidate>
          <CardHeader>
            <CardTitle>Profile</CardTitle>
            <CardDescription>Your name and email address.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="name">Full name</Label>
              <Input id="name" {...profileForm.register("name")} />
              <FieldError message={profileForm.formState.errors.name?.message} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="email">Email</Label>
              <Input id="email" value={user?.email ?? ""} disabled />
            </div>
          </CardContent>
          <CardFooter className="justify-end">
            <Button type="submit" isLoading={profileForm.formState.isSubmitting}>
              Save profile
            </Button>
          </CardFooter>
        </form>
      </Card>

      <Card>
        <form onSubmit={passwordForm.handleSubmit(onPasswordSubmit)} noValidate>
          <CardHeader>
            <CardTitle>Password</CardTitle>
            <CardDescription>Change your account password.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="current_password">Current password</Label>
              <Input id="current_password" type="password" {...passwordForm.register("current_password")} />
              <FieldError message={passwordForm.formState.errors.current_password?.message} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="new_password">New password</Label>
              <Input id="new_password" type="password" {...passwordForm.register("new_password")} />
              <FieldError message={passwordForm.formState.errors.new_password?.message} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="new_password_confirmation">Confirm new password</Label>
              <Input id="new_password_confirmation" type="password" {...passwordForm.register("new_password_confirmation")} />
              <FieldError message={passwordForm.formState.errors.new_password_confirmation?.message} />
            </div>
          </CardContent>
          <CardFooter className="justify-end">
            <Button type="submit" isLoading={passwordForm.formState.isSubmitting}>
              Update password
            </Button>
          </CardFooter>
        </form>
      </Card>

      <Card className="border-danger/30">
        <CardHeader>
          <CardTitle className="text-danger">Danger zone</CardTitle>
          <CardDescription>Permanently delete your account and all associated data.</CardDescription>
        </CardHeader>
        <CardFooter>
          <Dialog open={deleteOpen} onOpenChange={setDeleteOpen}>
            <DialogTrigger asChild>
              <Button variant="danger">Delete account</Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Delete your account</DialogTitle>
                <DialogDescription>
                  This permanently deletes your account, chatbots, and data. Enter your password to confirm.
                </DialogDescription>
              </DialogHeader>
              <div className="space-y-1.5">
                <Label htmlFor="delete-password">Password</Label>
                <Input
                  id="delete-password"
                  type="password"
                  value={deletePassword}
                  onChange={(e) => setDeletePassword(e.target.value)}
                />
              </div>
              <DialogFooter>
                <Button variant="danger" disabled={!deletePassword} isLoading={deleting} onClick={handleDeleteAccount}>
                  Permanently delete
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        </CardFooter>
      </Card>
    </div>
  );
}
