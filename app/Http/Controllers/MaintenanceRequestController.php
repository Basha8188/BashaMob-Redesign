<?php

namespace App\Http\Controllers;

use App\Mail\MaintenanceRequestReceived;
use App\Models\MaintenanceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MaintenanceRequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['required', 'regex:/^01[0-9]{9}$/'],
            'device' => ['required', 'in:موبايل,لابتوب,كمبيوتر'],
            'brand' => ['required', 'string', 'max:80'],
            'problem' => ['required', 'string', 'max:3000'],
        ]);

        $maintenanceRequest = MaintenanceRequest::create([
            ...$validated,
            'request_number' => $this->nextRequestNumber(),
            'status' => 'pending_review',
        ]);

        Mail::to($maintenanceRequest->email)->send(new MaintenanceRequestReceived($maintenanceRequest));

        return response()->json([
            'message' => 'تم تسجيل بياناتك والطلب قيد المراجعة، وتم إرسال رسالة تأكيد إلى بريدك الإلكتروني.',
            'request_number' => $maintenanceRequest->request_number,
            'pending_count' => MaintenanceRequest::where('status', 'pending_review')->count(),
        ], 201);
    }

    private function nextRequestNumber(): string
    {
        do {
            $number = 'BM-'.now()->format('Y').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (MaintenanceRequest::where('request_number', $number)->exists());

        return $number;
    }
}
