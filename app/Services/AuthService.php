<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Security\PasswordHasher;
use App\Core\Security\RateLimiter;
use App\Exceptions\AuthenticationException;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Models\User;
use App\Repositories\ActivityLogRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\EmailVerificationRepository;
use App\Repositories\PasswordResetRepository;
use App\Repositories\RoleRepository;
use App\Repositories\SessionRepository;
use App\Repositories\UserRepository;
use RuntimeException;

/**
 * Orchestrates the full authentication lifecycle. Controllers stay
 * thin — all business rules (lockout thresholds, token lifetimes,
 * email-enumeration-safe messaging) live here.
 */
final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly RoleRepository $roles,
        private readonly SessionRepository $sessions,
        private readonly PasswordResetRepository $passwordResets,
        private readonly EmailVerificationRepository $emailVerifications,
        private readonly AuditLogRepository $auditLogs,
        private readonly ActivityLogRepository $activityLogs,
        private readonly JwtService $jwt,
        private readonly TokenService $tokens,
        private readonly MailService $mail,
        private readonly PasswordHasher $hasher,
        private readonly RateLimiter $rateLimiter,
        private readonly WebhookDispatcherService $webhooks,
        private readonly GoogleAuthService $google,
    ) {
    }

    /**
     * @param array{name: string, email: string, password: string} $data
     * @return array{user: User, access_token: string, refresh_token: string, expires_in: int}
     */
    public function register(array $data, string $ip, string $userAgent): array
    {
        if ($this->users->emailExists($data['email'])) {
            throw new ConflictException('An account with this email address already exists.');
        }

        $defaultRole = $this->roles->findBySlug('user');

        if ($defaultRole === null) {
            throw new RuntimeException("Default role [user] is not seeded. Run the database seeder first.");
        }

        $userId = $this->users->create([
            'uuid' => str_uuid4(),
            'role_id' => $defaultRole['id'],
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'password' => $this->hasher->hash($data['password']),
            'status' => 'pending',
            'timezone' => $data['timezone'] ?? 'UTC',
            'locale' => $data['locale'] ?? 'en',
            'failed_login_attempts' => 0,
        ]);

        $this->issueEmailVerification((int) $userId, $data['email'], $data['name']);

        $this->auditLogs->record((int) $userId, 'user.registered', 'users', (int) $userId, $ip, $userAgent);
        $this->activityLogs->record((int) $userId, 'Account created.', 'users', (int) $userId);

        $userRow = $this->users->findWithRole((int) $userId);
        $user = User::fromArray($userRow);

        $this->webhooks->dispatch('user.created', ['user_id' => $user->uuid, 'name' => $user->name, 'email' => $user->email]);

        $tokens = $this->issueTokenPair($user, $ip, $userAgent);

        return ['user' => $user, ...$tokens];
    }

    /**
     * @return array{user: User, access_token: string, refresh_token: string, expires_in: int}
     */
    public function login(string $email, string $password, string $ip, string $userAgent): array
    {
        $email = mb_strtolower($email);
        $throttleKey = 'login:' . $ip . ':' . $email;

        $maxAttempts = (int) config('security.login_throttle.max_attempts', 5);
        $lockoutMinutes = (int) config('security.login_throttle.lockout_minutes', 15);

        if (!$this->rateLimiter->attempt($throttleKey, $maxAttempts, $lockoutMinutes * 60)) {
            $this->auditLogs->record(null, 'auth.login_throttled', null, null, $ip, $userAgent, ['email' => $email]);

            throw new AuthenticationException(
                'Too many login attempts. Please try again in a few minutes.',
                'LOGIN_THROTTLED'
            );
        }

        $row = $this->users->findByEmailWithRole($email);

        if ($row === null) {
            $this->auditLogs->record(null, 'auth.login_failed', null, null, $ip, $userAgent, ['email' => $email, 'reason' => 'not_found']);

            throw new AuthenticationException('The provided credentials are incorrect.', 'INVALID_CREDENTIALS');
        }

        $user = User::fromArray($row);

        if ($user->isLocked()) {
            $this->auditLogs->record($user->id, 'auth.login_blocked_locked', 'users', $user->id, $ip, $userAgent);

            throw new AuthenticationException(
                'This account is temporarily locked due to too many failed login attempts. Please try again later.',
                'ACCOUNT_LOCKED'
            );
        }

        if ($user->isSuspended()) {
            $this->auditLogs->record($user->id, 'auth.login_blocked_suspended', 'users', $user->id, $ip, $userAgent);

            throw new AuthenticationException('This account has been suspended. Please contact support.', 'ACCOUNT_SUSPENDED');
        }

        if (!$this->hasher->verify($password, $user->password)) {
            $this->users->incrementFailedLoginAttempts($user->id);

            $attempts = $user->failedLoginAttempts + 1;

            if ($attempts >= $maxAttempts) {
                $lockedUntil = now_utc()->modify("+{$lockoutMinutes} minutes")->format('Y-m-d H:i:s');
                $this->users->lockUntil($user->id, $lockedUntil);
                $this->auditLogs->record($user->id, 'auth.account_locked', 'users', $user->id, $ip, $userAgent);
            }

            $this->auditLogs->record($user->id, 'auth.login_failed', 'users', $user->id, $ip, $userAgent, ['reason' => 'bad_password']);

            throw new AuthenticationException('The provided credentials are incorrect.', 'INVALID_CREDENTIALS');
        }

        if ($this->hasher->needsRehash($user->password)) {
            $this->users->updatePassword($user->id, $this->hasher->hash($password));
        }

        $this->users->resetFailedLoginAttempts($user->id);
        $this->users->recordLogin($user->id, $ip);
        $this->rateLimiter->clear($throttleKey);

        $this->auditLogs->record($user->id, 'auth.login_succeeded', 'users', $user->id, $ip, $userAgent);
        $this->activityLogs->record($user->id, 'Signed in.', 'users', $user->id);

        $tokens = $this->issueTokenPair($user, $ip, $userAgent);

        return ['user' => $user, ...$tokens];
    }

    /**
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    public function refresh(string $rawRefreshToken, string $ip, string $userAgent): array
    {
        $tokenHash = $this->tokens->hash($rawRefreshToken);
        $session = $this->sessions->findValidByTokenHash($tokenHash);

        if ($session === null) {
            throw new AuthenticationException('The refresh token is invalid or has expired.', 'REFRESH_TOKEN_INVALID');
        }

        $userRow = $this->users->findWithRole((int) $session['user_id']);

        if ($userRow === null) {
            throw new AuthenticationException('The account for this session no longer exists.', 'USER_NOT_FOUND');
        }

        $user = User::fromArray($userRow);

        if ($user->isSuspended() || $user->isLocked()) {
            $this->sessions->revoke((int) $session['id']);

            throw new AuthenticationException('This account is no longer able to authenticate.', 'ACCOUNT_UNAVAILABLE');
        }

        // Rotate: revoke the old session and issue a brand new refresh token.
        $this->sessions->revoke((int) $session['id']);

        return $this->issueTokenPair($user, $ip, $userAgent);
    }

    public function logout(string $rawRefreshToken): void
    {
        $tokenHash = $this->tokens->hash($rawRefreshToken);
        $session = $this->sessions->findValidByTokenHash($tokenHash);

        if ($session !== null) {
            $this->sessions->revoke((int) $session['id']);
        }
    }

    public function logoutAllDevices(int $userId): void
    {
        $this->sessions->revokeAllForUser($userId);
    }

    /**
     * Always behaves identically whether or not the email exists, to
     * avoid leaking which addresses have accounts (user enumeration).
     */
    public function forgotPassword(string $email, string $resetUrlTemplate): void
    {
        $email = mb_strtolower($email);
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            return;
        }

        $this->passwordResets->invalidateForEmail($email);

        $rawToken = $this->tokens->generate();
        $tokenHash = $this->tokens->hash($rawToken);

        $this->passwordResets->create([
            'email' => $email,
            'token_hash' => $tokenHash,
            'expires_at' => now_utc()->modify('+60 minutes')->format('Y-m-d H:i:s'),
        ]);

        $resetUrl = str_replace('{token}', $rawToken, $resetUrlTemplate);
        $this->mail->sendPasswordReset($email, $user['name'], $resetUrl);
    }

    public function resetPassword(string $rawToken, string $newPassword): void
    {
        $tokenHash = $this->tokens->hash($rawToken);
        $reset = $this->passwordResets->findValidByTokenHash($tokenHash);

        if ($reset === null) {
            throw new AuthenticationException('This password reset link is invalid or has expired.', 'RESET_TOKEN_INVALID');
        }

        $user = $this->users->findByEmail($reset['email']);

        if ($user === null) {
            throw new NotFoundException('No account matches this password reset request.');
        }

        $this->users->updatePassword((int) $user['id'], $this->hasher->hash($newPassword));
        $this->passwordResets->markUsed((int) $reset['id']);
        $this->users->resetFailedLoginAttempts((int) $user['id']);

        // Force re-authentication everywhere for security.
        $this->sessions->revokeAllForUser((int) $user['id']);

        $this->activityLogs->record((int) $user['id'], 'Password was reset.', 'users', (int) $user['id']);
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): void
    {
        $userRow = $this->users->find($userId);

        if ($userRow === null) {
            throw new NotFoundException('User not found.');
        }

        if (!$this->hasher->verify($currentPassword, $userRow['password'])) {
            throw new AuthenticationException('The current password is incorrect.', 'INVALID_CURRENT_PASSWORD');
        }

        $this->users->updatePassword($userId, $this->hasher->hash($newPassword));

        // Keep the current device signed in but force all other
        // sessions to re-authenticate.
        $this->sessions->revokeAllForUser($userId);

        $this->activityLogs->record($userId, 'Password was changed.', 'users', $userId);
    }

    public function issueEmailVerification(int $userId, string $email, string $name): void
    {
        $this->emailVerifications->invalidateForUser($userId);

        $rawToken = $this->tokens->generate();
        $tokenHash = $this->tokens->hash($rawToken);

        $this->emailVerifications->create([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => now_utc()->modify('+24 hours')->format('Y-m-d H:i:s'),
        ]);

        // Points at the frontend's own verification page (which calls the
        // API and renders a result), matching the pattern already used by
        // the password-reset email below — not the raw API endpoint, which
        // would show the visitor unstyled JSON instead of a confirmation.
        // Query string, not a path segment — the frontend is a static
        // export with no server-side route matching, so its dynamic pages
        // read an id/token from ?query instead of a [param] path segment.
        $verificationUrl = rtrim((string) config('app.url'), '/') . '/verify-email?token=' . $rawToken;

        $this->mail->sendEmailVerification($email, $name, $verificationUrl);
    }

    public function resendVerificationEmail(string $email): void
    {
        $user = $this->users->findByEmail(mb_strtolower($email));

        if ($user === null || $user['email_verified_at'] !== null) {
            return;
        }

        $this->issueEmailVerification((int) $user['id'], $user['email'], $user['name']);
    }

    public function verifyEmail(string $rawToken): void
    {
        $tokenHash = $this->tokens->hash($rawToken);
        $verification = $this->emailVerifications->findValidByTokenHash($tokenHash);

        if ($verification === null) {
            throw new AuthenticationException('This verification link is invalid or has expired.', 'VERIFICATION_TOKEN_INVALID');
        }

        $this->users->markEmailVerified((int) $verification['user_id']);
        $this->emailVerifications->markUsed((int) $verification['id']);

        $this->activityLogs->record((int) $verification['user_id'], 'Email address verified.', 'users', (int) $verification['user_id']);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateProfile(int $userId, array $data): User
    {
        $allowed = array_intersect_key($data, array_flip(['name', 'timezone', 'locale']));

        if (!empty($allowed)) {
            $this->users->update($userId, $allowed);
        }

        $userRow = $this->users->findWithRole($userId);

        if ($userRow === null) {
            throw new NotFoundException('User not found.');
        }

        return User::fromArray($userRow);
    }

    public function deleteAccount(int $userId, string $password): void
    {
        $userRow = $this->users->find($userId);

        if ($userRow === null) {
            throw new NotFoundException('User not found.');
        }

        if (!$this->hasher->verify($password, $userRow['password'])) {
            throw new AuthenticationException('The provided password is incorrect.', 'INVALID_PASSWORD');
        }

        $this->sessions->revokeAllForUser($userId);
        $this->users->delete($userId);

        $this->activityLogs->record($userId, 'Account deleted.', 'users', $userId);
    }

    /**
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    /**
     * Sign in (or sign up) with a Google Identity Services ID token.
     * Google has already verified the email, so new accounts are
     * created active + email-verified, with an unguessable random
     * password (the user can set a real one via "forgot password").
     *
     * @return array{user: User, access_token: string, refresh_token: string, expires_in: int}
     */
    public function loginWithGoogle(string $idToken, string $ip, string $userAgent): array
    {
        $profile = $this->google->verify($idToken);

        $row = $this->users->findByEmailWithRole($profile['email']);

        if ($row === null) {
            $defaultRole = $this->roles->findBySlug('user');

            if ($defaultRole === null) {
                throw new NotFoundException('Default role [user] is not seeded. Run the database seeder first.');
            }

            $userId = (int) $this->users->create([
                'uuid' => str_uuid4(),
                'role_id' => $defaultRole['id'],
                'name' => mb_substr($profile['name'], 0, 150),
                'email' => $profile['email'],
                'password' => $this->hasher->hash(bin2hex(random_bytes(32))),
                'status' => 'active',
                'timezone' => 'UTC',
                'locale' => 'en',
                'failed_login_attempts' => 0,
            ]);

            $this->users->markEmailVerified($userId);

            $this->auditLogs->record($userId, 'user.registered_google', 'users', $userId, $ip, $userAgent);
            $this->activityLogs->record($userId, 'Account created via Google sign-in.', 'users', $userId);

            $user = User::fromArray($this->users->findWithRole($userId));

            $this->webhooks->dispatch('user.created', ['user_id' => $user->uuid, 'name' => $user->name, 'email' => $user->email]);
        } else {
            $user = User::fromArray($row);

            if ($user->isSuspended()) {
                $this->auditLogs->record($user->id, 'auth.login_blocked_suspended', 'users', $user->id, $ip, $userAgent);

                throw new AuthenticationException('This account has been suspended. Please contact support.', 'ACCOUNT_SUSPENDED');
            }

            if (!$user->hasVerifiedEmail()) {
                // Google vouches for the address — clear any pending
                // email-verification requirement.
                $this->users->markEmailVerified($user->id);
            }

            $this->auditLogs->record($user->id, 'auth.login_google', 'users', $user->id, $ip, $userAgent);
        }

        $this->users->update($user->id, [
            'last_login_at' => now_utc()->format('Y-m-d H:i:s'),
            'failed_login_attempts' => 0,
        ]);

        $freshUser = User::fromArray($this->users->findWithRole($user->id));
        $tokens = $this->issueTokenPair($freshUser, $ip, $userAgent);

        return ['user' => $freshUser, ...$tokens];
    }

    private function issueTokenPair(User $user, string $ip, string $userAgent): array
    {
        $accessToken = $this->jwt->issueAccessToken($user->uuid, [
            'role' => $user->roleSlug,
            'email' => $user->email,
        ]);

        $rawRefreshToken = $this->tokens->generate();
        $refreshTtlDays = (int) config('jwt.refresh_ttl_days', 30);

        $this->sessions->create([
            'uuid' => str_uuid4(),
            'user_id' => $user->id,
            'refresh_token_hash' => $this->tokens->hash($rawRefreshToken),
            'user_agent' => mb_substr($userAgent, 0, 255),
            'ip_address' => $ip,
            'is_revoked' => 0,
            'expires_at' => now_utc()->modify("+{$refreshTtlDays} days")->format('Y-m-d H:i:s'),
        ]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $rawRefreshToken,
            'expires_in' => $this->jwt->accessTtlSeconds(),
        ];
    }
}
