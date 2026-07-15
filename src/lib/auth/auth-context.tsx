"use client";

import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import { authApi } from "@/lib/api/endpoints";
import { ApiError } from "@/lib/api/client";
import { clearTokens, getRefreshToken, loadStoredTokens, setTokens } from "@/lib/api/client";
import type { User } from "@/lib/api/types";

interface AuthContextValue {
  user: User | null;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<User>;
  loginWithGoogle: (credential: string) => Promise<User>;
  register: (name: string, email: string, password: string) => Promise<User>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
  setUser: (user: User | null) => void;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    loadStoredTokens();
    authApi
      .me()
      .then(setUser)
      .catch(() => {
        clearTokens();
        setUser(null);
      })
      .finally(() => setIsLoading(false));
  }, []);

  const login = useCallback(async (email: string, password: string) => {
    const payload = await authApi.login({ email, password });
    setTokens(payload.access_token, payload.refresh_token);
    setUser(payload.user);
    return payload.user;
  }, []);

  const loginWithGoogle = useCallback(async (credential: string) => {
    const payload = await authApi.google(credential);
    setTokens(payload.access_token, payload.refresh_token);
    setUser(payload.user);
    return payload.user;
  }, []);

  const register = useCallback(async (name: string, email: string, password: string) => {
    const payload = await authApi.register({ name, email, password });
    setTokens(payload.access_token, payload.refresh_token);
    setUser(payload.user);
    return payload.user;
  }, []);

  const logout = useCallback(async () => {
    const refreshToken = getRefreshToken();
    try {
      if (refreshToken) await authApi.logout(refreshToken);
    } catch {
      // best-effort server-side revocation; always clear local state
    } finally {
      clearTokens();
      setUser(null);
    }
  }, []);

  const refreshUser = useCallback(async () => {
    try {
      const fresh = await authApi.me();
      setUser(fresh);
    } catch (error) {
      if (error instanceof ApiError && error.status === 401) {
        clearTokens();
        setUser(null);
      }
    }
  }, []);

  const value = useMemo(
    () => ({ user, isLoading, login, loginWithGoogle, register, logout, refreshUser, setUser }),
    [user, isLoading, login, loginWithGoogle, register, logout, refreshUser],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used within an AuthProvider");
  return ctx;
}
