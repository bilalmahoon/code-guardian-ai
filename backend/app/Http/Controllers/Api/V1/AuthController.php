<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Organization\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\Auth\AuthUserResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => $request->password,
            'email_verified_at' => now(), // auto-verify; send verification email separately
        ]);

        $user->assignRole('developer');

        $token = $user->createToken('auth_token', ['*'], now()->addMinutes(15))->plainTextToken;

        return $this->success([
            'user'         => new AuthUserResource($user),
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ], 'Registration successful', 201);
    }

    /**
     * Login with email and password.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Revoke previous tokens for this device if requested
        if ($request->boolean('revoke_existing')) {
            $user->tokens()->delete();
        }

        $user->update(['last_login_at' => now()]);

        $accessToken  = $user->createToken('access_token', ['*'], now()->addMinutes(15))->plainTextToken;
        $refreshToken = $user->createToken('refresh_token', ['token:refresh'], now()->addDays(30))->plainTextToken;

        return $this->success([
            'user'          => new AuthUserResource($user->load('organizations')),
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type'    => 'Bearer',
            'expires_in'    => 900, // 15 minutes in seconds
        ]);
    }

    /**
     * Refresh the access token using a valid refresh token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->currentAccessToken()->can('token:refresh')) {
            return $this->error('Invalid refresh token', 401);
        }

        // Invalidate the used refresh token (single-use rotation)
        $user->currentAccessToken()->delete();

        $accessToken  = $user->createToken('access_token', ['*'], now()->addMinutes(15))->plainTextToken;
        $refreshToken = $user->createToken('refresh_token', ['token:refresh'], now()->addDays(30))->plainTextToken;

        return $this->success([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type'    => 'Bearer',
            'expires_in'    => 900,
        ]);
    }

    /**
     * Logout and revoke all tokens.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->success(message: 'Successfully logged out');
    }

    /**
     * Redirect to OAuth provider (GitHub or Google).
     */
    public function redirectToProvider(Request $request, string $provider): JsonResponse
    {
        $this->validateProvider($provider);

        $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

        return $this->success(['redirect_url' => $url]);
    }

    /**
     * Handle the OAuth callback and issue tokens.
     */
    public function handleProviderCallback(Request $request, string $provider): JsonResponse
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Throwable $e) {
            return $this->error('OAuth authentication failed: ' . $e->getMessage(), 422);
        }

        $fieldName = $provider . '_id'; // github_id or google_id

        $user = User::updateOrCreate(
            ['email' => $socialUser->getEmail()],
            [
                'name'              => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                $fieldName          => $socialUser->getId(),
                'avatar_url'        => $socialUser->getAvatar(),
                'email_verified_at' => now(),
            ]
        );

        // Assign default role if new user
        if (! $user->hasAnyRole(['super_admin', 'org_admin', 'eng_manager', 'developer', 'qa_engineer'])) {
            $user->assignRole('developer');
        }

        $user->update(['last_login_at' => now()]);

        $accessToken  = $user->createToken('access_token', ['*'], now()->addMinutes(15))->plainTextToken;
        $refreshToken = $user->createToken('refresh_token', ['token:refresh'], now()->addDays(30))->plainTextToken;

        return $this->success([
            'user'          => new AuthUserResource($user->load('organizations')),
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type'    => 'Bearer',
            'expires_in'    => 900,
            'is_new_user'   => $user->wasRecentlyCreated,
        ]);
    }

    private function validateProvider(string $provider): void
    {
        if (! in_array($provider, ['github', 'google'])) {
            abort(404, "Unsupported OAuth provider: {$provider}");
        }
    }

    /**
     * Get the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success([
            'user' => new AuthUserResource($request->user()->load('organizations')),
        ]);
    }
}
