"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { FieldError, Input, Label, Textarea } from "@/components/ui/input";
import { ApiError } from "@/lib/api/client";
import { botsApi } from "@/lib/api/endpoints";

const schema = z.object({
  name: z.string().min(2, "Give your chatbot a name").max(150),
  description: z.string().max(1000).optional().or(z.literal("")),
  welcome_message: z.string().max(500).optional().or(z.literal("")),
});
type Values = z.infer<typeof schema>;

export default function NewBotPage() {
  const router = useRouter();
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: { name: "", description: "", welcome_message: "Hi! How can I help you today?" },
  });

  async function onSubmit(values: Values) {
    try {
      const bot = await botsApi.create(values);
      toast.success("Chatbot created — now add some knowledge sources.");
      router.push(`/bots/${bot.id}`);
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not create the chatbot.");
    }
  }

  return (
    <div className="mx-auto max-w-2xl">
      <Card>
        <CardHeader>
          <CardTitle>Create a new chatbot</CardTitle>
          <CardDescription>Give it a name and greeting — you can train it on content next.</CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-5">
            <div className="space-y-1.5">
              <Label htmlFor="name">Chatbot name</Label>
              <Input id="name" placeholder="Support Assistant" {...register("name")} />
              <FieldError message={errors.name?.message} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="description">Description (internal)</Label>
              <Textarea id="description" rows={2} placeholder="What is this chatbot for?" {...register("description")} />
              <FieldError message={errors.description?.message} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="welcome_message">Welcome message</Label>
              <Textarea id="welcome_message" rows={2} {...register("welcome_message")} />
              <FieldError message={errors.welcome_message?.message} />
            </div>
            <div className="flex justify-end gap-3">
              <Button type="button" variant="outline" onClick={() => router.back()}>
                Cancel
              </Button>
              <Button type="submit" isLoading={isSubmitting}>
                Create chatbot
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
