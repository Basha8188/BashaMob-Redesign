<?php

namespace App\Http\Controllers;

use App\Services\AiActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiActionController extends Controller
{
    public function __construct(private AiActionService $actions) {}

    public function confirm(Request $request, string $token): JsonResponse
    {
        $data = $request->validate(['locale' => ['nullable', 'in:ar,en']]);

        return response()->json($this->actions->confirmSale(
            $request->user(),
            $token,
            $data['locale'] ?? 'ar',
        ));
    }
}
