<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Models\User;
use App\Requests\Auth\ChangePasswordRequest;
use App\Requests\Auth\DeleteAccountRequest;
use App\Requests\Auth\ForgotPasswordRequest;
use App\Requests\Auth\LoginRequest;
use App\Requests\Auth\RefreshTokenRequest;
use App\Requests\Auth\RegisterRequest;
use App\Requests\Auth\ResendVerificationRequest;
use App\Requests\Auth\ResetPasswordRequest;
use App\Requests\Auth\UpdateProfileRequest;
use App\Resources\UserResource;
use App\Services\AuthService;

final class AuthController
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function register(Request $request): Response
    {
        $data = RegisterRequest::validate($request);

        $result = $this->authService->register($data, $request->ip(), $request->userAgent());

        return JsonResponse::created(
            UserResource::withTokens($result),
            'Account created successfully. Please check your email to verify your address.'
        );
    }

    public function login(Request $request): Response
    {
        $data = LoginRequest::validate($request);

        $result = $this->authService->login($data['email'], $data['password'], $request->ip(), $request->userAgent());

        return JsonResponse::success(UserResource::withTokens($result), 'Logged in successfully.');
    }

    public function googleLogin(Request $request): Response
    {
        $credential = $request->input('credential');

        if (!is_string($credential) || $credential === '') {
            throw new \App\Exceptions\ValidationException(['credential' => ['A Google credential is required.']]);
        }

        $result = $this->authService->loginWithGoogle($credential, $request->ip(), $request->userAgent());

        return JsonResponse::success(UserResource::withTokens($result), 'Logged in with Google successfully.');
    }

    public function refresh(Request $request): Response
    {
        $data = RefreshTokenRequest::validate($request);

        $tokens = $this->authService->refresh($data['refresh_token'], $request->ip(), $request->userAgent());

        return JsonResponse::success($tokens, 'Token refreshed successfully.');
    }

    public function logout(Request $request): Response
    {
        $data = RefreshTokenRequest::validate($request);

        $this->authService->logout($data['refresh_token']);

        return JsonResponse::success(null, 'Logged out successfully.');
    }

    public function logoutAllDevices(Request $request): Response
    {
        $user = $this->currentUser($request);

        $this->authService->logoutAllDevices($user->id);

        return JsonResponse::success(null, 'Logged out from all devices successfully.');
    }

    public function forgotPassword(Request $request): Response
    {
        $data = ForgotPasswordRequest::validate($request);

        $frontendUrl = rtrim((string) config('app.url'), '/') . '/reset-password?token={token}';
        $this->authService->forgotPassword($data['email'], $frontendUrl);

        return JsonResponse::success(
            null,
            'If an account exists for this email address, a password reset link has been sent.'
        );
    }

    public function resetPassword(Request $request): Response
    {
        $data = ResetPasswordRequest::validate($request);

        $this->authService->resetPassword($data['token'], $data['password']);

        return JsonResponse::success(null, 'Password has been reset successfully. Please log in again.');
    }

    public function changePassword(Request $request): Response
    {
        $user = $this->currentUser($request);
        $data = ChangePasswordRequest::validate($request);

        $this->authService->changePassword($user->id, $data['current_password'], $data['new_password']);

        return JsonResponse::success(null, 'Password changed successfully.');
    }

    public function verifyEmail(Request $request, string $token): Response
    {
        $this->authService->verifyEmail($token);

        return JsonResponse::success(null, 'Email address verified successfully.');
    }

    public function resendVerification(Request $request): Response
    {
        $data = ResendVerificationRequest::validate($request);

        $this->authService->resendVerificationEmail($data['email']);

        return JsonResponse::success(
            null,
            'If an account exists for this email address and is not yet verified, a new verification email has been sent.'
        );
    }

    public function me(Request $request): Response
    {
        $user = $this->currentUser($request);

        return JsonResponse::success(UserResource::make($user), 'Current user retrieved successfully.');
    }

    public function updateProfile(Request $request): Response
    {
        $user = $this->currentUser($request);
        $data = UpdateProfileRequest::validate($request);

        $updated = $this->authService->updateProfile($user->id, $data);

        return JsonResponse::success(UserResource::make($updated), 'Profile updated successfully.');
    }

    public function deleteAccount(Request $request): Response
    {
        $user = $this->currentUser($request);
        $data = DeleteAccountRequest::validate($request);

        $this->authService->deleteAccount($user->id, $data['password']);

        return JsonResponse::success(null, 'Account deleted successfully.');
    }

    private function currentUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        return $user;
    }
}
