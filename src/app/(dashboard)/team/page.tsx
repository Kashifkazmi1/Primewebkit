"use client";

import { Trash2, UserPlus, Users } from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { EmptyState } from "@/components/ui/empty-state";
import { Input, Label } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { ApiError } from "@/lib/api/client";
import { teamsApi } from "@/lib/api/endpoints";
import type { Team, TeamMember } from "@/lib/api/types";
import { useAuth } from "@/lib/auth/auth-context";
import { formatDate, initials } from "@/lib/utils";

export default function TeamPage() {
  const { user } = useAuth();
  const [team, setTeam] = useState<Team | null>(null);
  const [members, setMembers] = useState<TeamMember[] | null>(null);
  const [inviteOpen, setInviteOpen] = useState(false);
  const [email, setEmail] = useState("");
  const [role, setRole] = useState("member");
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    teamsApi
      .list()
      .then(async (teams) => {
        const first = teams[0] ?? null;
        setTeam(first);
        if (first) setMembers(await teamsApi.members(first.id));
        else setMembers([]);
      })
      .catch(() => {
        setTeam(null);
        setMembers([]);
      });
  }, []);

  async function handleInvite() {
    if (!team) return;
    setSubmitting(true);
    try {
      await teamsApi.invite(team.id, { email, role });
      toast.success(`Invitation sent to ${email}.`);
      setInviteOpen(false);
      setEmail("");
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not send the invitation.");
    } finally {
      setSubmitting(false);
    }
  }

  async function handleRemove(targetUserId: string) {
    if (!team) return;
    try {
      await teamsApi.removeMember(team.id, targetUserId);
      setMembers((prev) => prev?.filter((m) => m.user_id !== targetUserId) ?? prev);
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not remove this member.");
    }
  }

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h1 className="font-display text-2xl font-semibold tracking-tight">Team</h1>
          <p className="text-sm text-muted-foreground">Invite teammates and manage their access.</p>
        </div>
        <Dialog open={inviteOpen} onOpenChange={setInviteOpen}>
          <DialogTrigger asChild>
            <Button disabled={!team}>
              <UserPlus className="size-4" /> Invite member
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Invite a teammate</DialogTitle>
              <DialogDescription>They&apos;ll receive an email invitation to join your team.</DialogDescription>
            </DialogHeader>
            <div className="space-y-4">
              <div className="space-y-1.5">
                <Label htmlFor="invite-email">Email</Label>
                <Input id="invite-email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="invite-role">Role</Label>
                <Select value={role} onValueChange={setRole}>
                  <SelectTrigger id="invite-role">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="member">Member</SelectItem>
                    <SelectItem value="manager">Manager</SelectItem>
                    <SelectItem value="owner">Owner</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
            <DialogFooter>
              <Button onClick={handleInvite} disabled={!email} isLoading={submitting}>
                Send invitation
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>

      {members === null ? (
        <Skeleton className="h-32" />
      ) : !team || members.length === 0 ? (
        <EmptyState icon={Users} title="No team yet" description="Create a team to invite others to collaborate." />
      ) : (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Member</TableHead>
              <TableHead>Role</TableHead>
              <TableHead>Joined</TableHead>
              <TableHead className="sr-only">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {members.map((member) => (
              <TableRow key={member.id}>
                <TableCell>
                  <div className="flex items-center gap-3">
                    <Avatar>
                      <AvatarFallback>{initials(member.name)}</AvatarFallback>
                    </Avatar>
                    <div>
                      <p className="font-medium">{member.name}</p>
                      <p className="text-xs text-muted-foreground">{member.email}</p>
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  <Badge variant="outline">{member.role}</Badge>
                </TableCell>
                <TableCell className="text-sm text-muted-foreground">{formatDate(member.joined_at)}</TableCell>
                <TableCell>
                  {member.user_id !== user?.id && (
                    <Button variant="ghost" size="icon" aria-label="Remove member" onClick={() => handleRemove(member.user_id)}>
                      <Trash2 className="size-4" />
                    </Button>
                  )}
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}
    </div>
  );
}
