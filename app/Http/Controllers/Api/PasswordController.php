<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuthVerificationToken;
use App\Models\User;
use App\Services\PasswordResetDelivery;
use App\Support\VietnamesePhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    private function passwordRules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    private function messages(): array
    {
        return [
            'password.min' => 'Mật khẩu cần có ít nhất 8 ký tự.',
            'password.max' => 'Mật khẩu tối đa 128 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận chưa khớp.',
            'password.different' => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
            'otp.regex' => 'Mã OTP phải gồm đúng 6 chữ số.',
        ];
    }

    private function identity(Request $request): array
    {
        $input = $request->validate(['identifier' => ['required', 'string', 'max:191']])['identifier'];
        $identifier = trim($input);
        if (str_contains($identifier, '@')) {
            if (! filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages(['identifier' => 'Vui lòng nhập email hoặc số điện thoại hợp lệ.']);
            }

            return [mb_strtolower($identifier), 'email'];
        }
        $identifier = VietnamesePhone::normalize($identifier);
        if (! preg_match('/^\+84[35789][0-9]{8}$/D', $identifier)) {
            throw ValidationException::withMessages(['identifier' => 'Vui lòng nhập email hoặc số điện thoại Việt Nam hợp lệ.']);
        }

        return [$identifier, 'sms'];
    }

    private function account(string $identifier, string $channel): ?User
    {
        $query = User::query()->where('status', 'active');

        return $channel === 'email'
            ? $query->where('email', $identifier)->first()
            : $query->whereIn('phone', VietnamesePhone::variants($identifier))->first();
    }

    public function change(Request $request)
    {
        $rules = $this->passwordRules();
        $rules['current_password'] = ['required', 'string', 'max:128'];
        $rules['password'][] = 'different:current_password';
        $data = $request->validate($rules, $this->messages());
        $user = $request->user();
        DB::transaction(function () use ($user, $data) {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (! Hash::check($data['current_password'], $locked->password)) {
                throw ValidationException::withMessages(['current_password' => 'Mật khẩu hiện tại không chính xác.']);
            }
            $locked->update(['password' => $data['password'], 'remember_token' => null]);
            $currentToken = $user->currentAccessToken();
            $tokens = $locked->tokens();
            if ($currentToken instanceof \Laravel\Sanctum\PersonalAccessToken) {
                $tokens->where('id', '!=', $currentToken->id);
            }
            $tokens->delete();
            AuthVerificationToken::where('user_id', $user->id)->where('purpose', 'reset_password')->whereNull('used_at')->update(['used_at' => now()]);
        });

        return response()->json(['message' => 'Đổi mật khẩu thành công. Các phiên đăng nhập khác đã được thu hồi.']);
    }

    public function forgot(Request $request, PasswordResetDelivery $delivery)
    {
        [$identifier, $channel] = $this->identity($request);
        $delivery->ensureAvailable($channel);
        $user = $this->account($identifier, $channel);
        $response = ['message' => 'Nếu tài khoản hợp lệ, mã xác nhận đã được gửi. Mã có hiệu lực trong 15 phút.'];
        if (! $user) {
            return response()->json($response);
        }
        $otp = (string) random_int(100000, 999999);
        $record = DB::transaction(function () use ($user, $identifier, $channel, $otp) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            AuthVerificationToken::where('user_id', $user->id)->where('purpose', 'reset_password')->whereNull('used_at')->update(['used_at' => now()]);

            return AuthVerificationToken::create([
                'user_id' => $user->id, 'destination' => $identifier, 'channel' => $channel,
                'purpose' => 'reset_password', 'token_hash' => Hash::make($otp),
                'expires_at' => now()->addMinutes(config('password_reset.ttl_minutes')),
                'attempt_count' => 0,
            ]);
        });
        try {
            $delivery->send($identifier, $channel, $otp);
        } catch (\Throwable $exception) {
            $record->update(['used_at' => now()]);
            // Do not log exception payloads from an SMS provider: they may contain the OTP.
            abort(503, 'Chưa thể gửi mã xác nhận. Vui lòng thử lại sau.');
        }
        if ($delivery->isDebug()) {
            $response['debug_otp'] = $otp;
        }

        return response()->json($response);
    }

    public function reset(Request $request)
    {
        [$identifier, $channel] = $this->identity($request);
        $data = $request->validate($this->passwordRules() + ['otp' => ['required', 'string', 'regex:/^[0-9]{6}$/D']], $this->messages());
        $user = $this->account($identifier, $channel);
        $success = $user && DB::transaction(function () use ($user, $identifier, $data) {
            $locked = User::whereKey($user->id)->where('status', 'active')->lockForUpdate()->first();
            if (! $locked) {
                return false;
            }
            $record = AuthVerificationToken::where('user_id', $user->id)->where('destination', $identifier)
                ->where('purpose', 'reset_password')->latest('id')->lockForUpdate()->first();
            if (! $record || $record->used_at || $record->expires_at->isPast() || $record->attempt_count >= config('password_reset.max_attempts')) {
                return false;
            }
            if (! Hash::check($data['otp'], $record->token_hash)) {
                $record->attempt_count++;
                if ($record->attempt_count >= config('password_reset.max_attempts')) {
                    $record->used_at = now();
                }
                $record->save();

                // Return before throwing so failed attempts are committed, not rolled back.
                return false;
            }
            $locked->update(['password' => $data['password'], 'remember_token' => null]);
            AuthVerificationToken::where('user_id', $user->id)->where('purpose', 'reset_password')->whereNull('used_at')->update(['used_at' => now()]);
            $locked->tokens()->delete();

            return true;
        });
        if (! $success) {
            throw ValidationException::withMessages(['otp' => 'Mã xác nhận không hợp lệ, đã hết hạn hoặc đã được sử dụng.']);
        }

        return response()->json(['message' => 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập lại.']);
    }
}
