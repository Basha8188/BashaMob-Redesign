<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendStaffRecoveryEmail;
use App\Jobs\SendStaffRecoveryWhatsapp;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffRecoveryController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'method' => ['required', Rule::in(['email', 'whatsapp'])],
            'destination' => ['required', 'string', 'max:190'],
        ]);

        $destination = $data['method'] === 'email' ? Str::lower(trim($data['destination'])) : $this->normalizePhone($data['destination']);
        $sendKey = 'staff-recovery-send:'.hash('sha256', $request->ip().'|'.$destination);
        abort_if(RateLimiter::tooManyAttempts($sendKey, 3), 429, 'حاول مرة أخرى بعد قليل.');
        RateLimiter::hit($sendKey, 600);

        $user = User::query()->where('is_staff', true)->where('is_active', true)->whereNull('suspended_at')
            ->where($data['method'] === 'email' ? 'email' : 'phone', $destination)->first();

        $code = (string) random_int(100000, 999999);
        $challenge = (string) Str::uuid();
        Cache::put("staff-recovery:{$challenge}", [
            'user_id' => $user?->id,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(10)->timestamp,
        ], now()->addMinutes(10));

        if ($user && $data['method'] === 'email') {
            SendStaffRecoveryEmail::dispatch($user->email, $code);
        }
        if ($user && $data['method'] === 'whatsapp') {
            SendStaffRecoveryWhatsapp::dispatch($user->phone, $code);
        }

        return response()->json([
            'message' => 'إذا كانت البيانات مسجلة فسيصلك كود التحقق خلال لحظات.',
            'challenge' => $challenge,
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate(['challenge' => ['required', 'uuid'], 'code' => ['required', 'digits:6']]);
        $key = "staff-recovery:{$data['challenge']}";
        $attemptKey = "staff-recovery-attempts:{$data['challenge']}";
        $recovery = Cache::get($key);

        $invalid = ! $recovery || $recovery['expires_at'] < now()->timestamp || ! $recovery['user_id'] || ! Hash::check($data['code'], $recovery['code']);
        if ($invalid) {
            RateLimiter::hit($attemptKey, 600);
            if (RateLimiter::attempts($attemptKey) >= 5) {
                Cache::forget($key);
            }

            return response()->json(['message' => 'الكود غير صحيح أو انتهت صلاحيته.'], 422);
        }

        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            Cache::forget($key);

            return response()->json(['message' => 'تم تجاوز عدد المحاولات المسموح.'], 422);
        }

        $resetToken = Str::random(72);
        Cache::put('staff-reset:'.hash('sha256', $resetToken), $recovery['user_id'], now()->addMinutes(5));
        Cache::forget($key);
        RateLimiter::clear($attemptKey);

        return response()->json(['message' => 'تم التحقق من الكود.', 'reset_token' => $resetToken]);
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reset_token' => ['required', 'string', 'min:60', 'max:100'],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()],
        ]);

        $userId = Cache::pull('staff-reset:'.hash('sha256', $data['reset_token']));
        $user = $userId ? User::query()->where('is_staff', true)->where('is_active', true)->find($userId) : null;
        if (! $user) {
            return response()->json(['message' => 'انتهت صلاحية رابط تغيير كلمة المرور.'], 422);
        }

        $user->forceFill([
            'password' => $data['password'],
            'password_changed_at' => now(),
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('sessions')->where('user_id', $user->id)->delete();
        ActivityLogger::record('staff.password_reset', $user, newValues: ['password_changed_at' => now()->toIso8601String()], request: $request);

        return response()->json(['message' => 'تم تغيير كلمة المرور بنجاح. يمكنك تسجيل الدخول الآن.']);
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }
        if (str_starts_with($phone, '0')) {
            $phone = '20'.substr($phone, 1);
        }

        return $phone;
    }
}
