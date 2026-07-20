<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAssistantController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'page' => ['nullable', 'string', 'max:200'],
            'locale' => ['nullable', 'in:ar,en'],
            'history' => ['nullable', 'array', 'max:8'],
            'history.*.role' => ['required_with:history', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:1500'],
        ]);

        $key = config('services.openai.key');
        if (! $key) {
            return response()->json([
                'message' => ($data['locale'] ?? 'ar') === 'en'
                    ? 'The AI assistant is ready, but OPENAI_API_KEY must be added to the .env file first.'
                    : 'المساعد الذكي جاهز، لكن يلزم إضافة OPENAI_API_KEY داخل ملف .env أولًا.',
            ], 503);
        }

        $page = $data['page'] ?? 'BashaMob';
        $input = collect($data['history'] ?? [])
            ->take(-8)
            ->map(fn (array $message) => [
                'role' => $message['role'],
                'content' => $message['content'],
            ])
            ->push(['role' => 'user', 'content' => $data['question']])
            ->values()
            ->all();

        try {
            $response = Http::withToken($key)
                ->acceptJson()
                ->timeout(45)
                ->retry(2, 350, throw: false)
                ->post(config('services.openai.url'), [
                    'model' => config('services.openai.model'),
                    'instructions' => "You are the capable BashaMob AI assistant. Answer arbitrary general questions accurately and directly, not only questions about the website. When the question concerns BashaMob, use the current page context: {$page}. Reply in the same language as the user. Keep answers useful and concise. Never claim that you performed an action, accessed private business data, or changed a record unless the application explicitly supplied proof.",
                    'input' => $input,
                    'reasoning' => ['effort' => 'low'],
                    'text' => ['verbosity' => 'medium'],
                    'max_output_tokens' => 700,
                    'store' => false,
                ]);

            if (! $response->successful()) {
                Log::warning('OpenAI assistant request failed', ['status' => $response->status()]);

                return response()->json([
                    'message' => $response->status() === 429
                        ? 'المساعد مشغول حاليًا. انتظر لحظة ثم حاول مرة أخرى.'
                        : 'تعذر الوصول إلى المساعد الذكي الآن. حاول مرة أخرى بعد قليل.',
                ], $response->status() === 429 ? 429 : 502);
            }

            $payload = $response->json();
            $answer = collect($payload['output'] ?? [])
                ->where('type', 'message')
                ->flatMap(fn (array $item) => $item['content'] ?? [])
                ->where('type', 'output_text')
                ->pluck('text')
                ->filter()
                ->implode("\n");

            if (! $answer) {
                return response()->json(['message' => 'لم يصل رد مكتمل من المساعد. حاول صياغة السؤال مرة أخرى.'], 502);
            }

            return response()->json(['answer' => $answer]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'حدث خطأ أثناء الاتصال بالمساعد الذكي. حاول مرة أخرى.'], 502);
        }
    }
}
