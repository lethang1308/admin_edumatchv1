<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterStudentRequest;
use App\Models\User;
use App\Support\VietnamesePhone;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function register(RegisterStudentRequest $request): JsonResponse
    {
        $data = $request->validated();
        try {
            [$user, $token] = DB::transaction(function () use ($data) {
                $user = User::create([
                    'role' => 'student',
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'password' => $data['password'],
                    'status' => 'active',
                ]);
                $user->studentProfile()->create([
                    'full_name' => $user->name,
                    'date_of_birth' => $data['date_of_birth'],
                ]);
                $user->assignRole(Role::findOrCreate('student', 'web'));

                return [$user, $user->createToken('auth_token')->plainTextToken];
            });
        } catch (QueryException $exception) {
            // A concurrent registration can pass validation before the unique insert.
            if (($exception->errorInfo[1] ?? null) === 1062) {
                $message = $exception->errorInfo[2] ?? '';
                if (str_contains($message, 'users_phone_unique')) {
                    throw ValidationException::withMessages(['phone' => 'Số điện thoại này đã được sử dụng.']);
                }
                if (str_contains($message, 'users_email_unique')) {
                    throw ValidationException::withMessages(['email' => 'Email này đã được sử dụng.']);
                }
            }
            throw $exception;
        }

        return $this->tokenResponse($user, $token, 'Đăng ký tài khoản học viên thành công.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $identifier = trim($data['identifier']);
        $query = User::query();
        $user = str_contains($identifier, '@')
            ? $query->where('email', mb_strtolower($identifier))->first()
            : $query->whereIn('phone', VietnamesePhone::variants($identifier))->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Thông tin đăng nhập hoặc mật khẩu không chính xác.'], 401);
        }
        if ($user->status === 'blocked') {
            return $this->blockedResponse();
        }

        $token = DB::transaction(function () use ($user) {
            $user->update(['last_login_at' => now()]);

            return $user->createToken('auth_token')->plainTextToken;
        });

        return $this->tokenResponse($user, $token, 'Đăng nhập thành công.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->status === 'blocked') {
            return $this->blockedResponse();
        }

        return response()->json([
            'user' => $user->load('studentProfile'),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        return $this->me($request);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => 'Đăng xuất thành công.']);
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->status === 'blocked') {
            return $this->blockedResponse();
        }
        $token = DB::transaction(function () use ($user) {
            $currentToken = $user->currentAccessToken();
            if ($currentToken instanceof \Laravel\Sanctum\PersonalAccessToken) {
                $currentToken->delete();
            }

            return $user->createToken('auth_token')->plainTextToken;
        });

        return $this->tokenResponse($user, $token, 'Làm mới phiên đăng nhập thành công.');
    }

    private function tokenResponse(User $user, string $token, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'user' => $user->load('studentProfile'),
            'access_token' => $token,
            'token' => $token,
            'token_type' => 'Bearer',
        ], $status);
    }

    private function blockedResponse(): JsonResponse
    {
        return response()->json(['message' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.'], 403);
    }
}
