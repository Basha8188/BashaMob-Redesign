<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    public function test_it_explains_when_the_api_key_is_missing(): void
    {
        config(['services.openai.key' => null]);

        $this->postJson('/assistant/ask', [
            'question' => 'ما حالة المخزون؟',
            'locale' => 'ar',
        ])->assertStatus(503)->assertJsonStructure(['message']);
    }

    public function test_it_returns_the_response_api_answer(): void
    {
        config([
            'services.openai.key' => 'test-key',
            'services.openai.model' => 'gpt-5.6-luna',
            'services.openai.url' => 'https://api.openai.com/v1/responses',
        ]);

        Http::fake([
            'api.openai.com/v1/responses' => Http::response([
                'output' => [[
                    'type' => 'message',
                    'content' => [['type' => 'output_text', 'text' => 'هذه إجابة مفيدة.']],
                ]],
            ]),
        ]);

        $this->postJson('/assistant/ask', [
            'question' => 'اشرح لي الفاتورة',
            'page' => 'المبيعات',
            'locale' => 'ar',
        ])->assertOk()->assertJsonPath('answer', 'هذه إجابة مفيدة.');

        Http::assertSent(fn ($request) => $request['model'] === 'gpt-5.6-luna'
            && $request['input'][0]['content'] === 'اشرح لي الفاتورة');
    }
}
