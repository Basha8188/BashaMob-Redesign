<?php

namespace App\Http\Controllers;

use App\Jobs\SendMaintenanceRequestConfirmation;
use App\Models\Customer;
use App\Models\MaintenanceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

class MaintenanceRequestController extends Controller
{
    public function track(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:3', 'max:120'],
        ]);

        $query = trim($validated['query']);
        $phone = preg_replace('/\D+/', '', $query);
        $requests = MaintenanceRequest::query()
            ->where('request_number', $query)
            ->orWhere('phone', $phone)
            ->orWhere('name', $query)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (MaintenanceRequest $maintenanceRequest) => [
                'request_number' => $maintenanceRequest->request_number,
                'name' => $maintenanceRequest->name,
                'device' => $maintenanceRequest->device,
                'brand' => $maintenanceRequest->brand,
                'status' => $this->statusLabel($maintenanceRequest->status),
                'status_key' => $maintenanceRequest->status,
                'review_message' => $maintenanceRequest->review_message,
                'submitted_at' => $maintenanceRequest->created_at->translatedFormat('j F Y، g:i a'),
            ]);

        return response()->json([
            'message' => $requests->isEmpty() ? 'لم نجد طلبًا مطابقًا. تأكد من رقم الطلب أو الهاتف أو الاسم بالكامل.' : 'تم العثور على طلبك.',
            'requests' => $requests,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['required', 'regex:/^01[0-9]{9}$/'],
            'device' => ['required', 'in:موبايل,لابتوب,كمبيوتر'],
            'brand' => ['required', 'string', 'max:80'],
            'problem' => ['required', 'string', 'max:3000'],
            'photos' => ['nullable', 'array', 'max:4'],
            'photos.*' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(5 * 1024)],
        ]);

        $attachments = collect($request->file('photos', []))->map(fn ($photo) => $photo->store('maintenance-requests', 'public'))->all();
        unset($validated['photos']);

        $maintenanceRequest = DB::transaction(function () use ($validated, $attachments) {
            $customer = Customer::withTrashed()->firstOrNew(['phone' => $validated['phone']]);
            $customer->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_active' => true,
                'deleted_at' => null,
            ])->save();

            return MaintenanceRequest::create([
                ...$validated,
                'attachments' => $attachments,
                'request_number' => $this->nextRequestNumber(),
                'status' => 'pending_review',
            ]);
        });

        SendMaintenanceRequestConfirmation::dispatch($maintenanceRequest->id);

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

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending_review' => 'قيد المراجعة',
            'approved' => 'تمت الموافقة',
            'rejected' => 'مرفوض',
            'received' => 'تم استلام الجهاز',
            'in_progress' => 'قيد الإصلاح',
            'waiting_parts' => 'بانتظار قطعة غيار',
            'ready_for_delivery' => 'جاهز للاستلام',
            'delivered' => 'تم التسليم',
            'cancelled' => 'ملغي',
            default => 'قيد المتابعة',
        };
    }
}
