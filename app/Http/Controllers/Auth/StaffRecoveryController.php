<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\StaffRecoveryCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StaffRecoveryController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'method' => ['required', Rule::in(['email', 'whatsapp'])],
            'destination' => ['required', 'string', 'max:190'],
        ]);

        $user = $data['method'] === 'email'
            ? User::where('email', Str::lower($data['destination']))->first()
            : User::where('phone', $this->normalizePhone($data['destination']))->first();

        if (! $user) {
            return response()->json(['message' => 'إذا كانت البيانات مسجلة فسيصلك كود التحقق خلال لحظات.']);
        }

        $code = (string) random_int(100000, 999999);
        $challenge = (string) Str::uuid();

        Cache::put("staff-recovery:{$challenge}", [
            'user_id' => $user->id,
            'code' => Hash::make($code),
            'attempts' => 0,
        ], now()->addMinutes(10));

        if ($data['method'] === 'email') {
            Mail::to($user->email)->send(new StaffRecoveryCode($code));
        } else {
            $this->sendWhatsAppCode($user->phone, $code);
        }

        return response()->json([
            'message' => 'تم إرسال كود التحقق. الكود صالح لمدة 10 دقائق.',
            'challenge' => $challenge,
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'challenge' => ['required', 'uuid'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $key = "staff-recovery:{$data['challenge']}";
        $recovery = Cache::get($key);

        if (! $recovery || $recovery['attempts'] >= 5 || ! Hash::check($data['code'], $recovery['code'])) {
            if ($recovery) {
                $recovery['attempts']++;
                Cache::put($key, $recovery, now()->addMinutes(10));
            }
            return response()->json(['message' => 'الكود غير صحيح أو انتهت صلاحيته.'], 422);
        }

        $user = User::find($recovery['user_id']);
        if (! $user) {
            Cache::forget($key);
            return response()->json(['message' => 'تعذر تحديث الحساب.'], 422);
        }

        $user->update(['password' => $data['password']]);
        Cache::forget($key);

        return response()->json(['message' => 'تم تغيير كلمة المرور بنجاح. يمكنك تسجيل الدخول الآن.']);
    }

    private function sendWhatsAppCode(?string $phone, string $code): void
    {
        $url = config('services.whatsapp.url');
        $token = config('services.whatsapp.token');

        abort_unless($url && $token && $phone, 503, 'خدمة WhatsApp غير مهيأة بعد. أضف بيانات WhatsApp Business API.');

        Http::withToken($token)->acceptJson()->post($url, [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => config('services.whatsapp.template', 'password_reset_code'),
                'language' => ['code' => config('services.whatsapp.language', 'ar')],
                'components' => [[
                    'type' => 'body',
                    'parameters' => [['type' => 'text', 'text' => $code]],
                ]],
            ],
        ])->throw();
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($phone, '00')) $phone = substr($phone, 2);
        if (str_starts_with($phone, '0')) $phone = '20'.substr($phone, 1);
        return $phone;
    }
}
