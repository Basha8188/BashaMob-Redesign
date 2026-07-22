<?php

namespace App\Services;

use App\Enums\RepairStatus;
use App\Models\PublicAccessToken;
use App\Models\RepairQuotation;
use App\Models\RepairTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RepairQuotationService
{
    public function __construct(private NumberSequenceService $numbers, private RepairService $repairs, private DevicePassportService $passports) {}

    public function create(RepairTicket $ticket, array $data, int $userId): array
    {
        return DB::transaction(function () use ($ticket, $data, $userId) {
            $ticket = RepairTicket::with(['inspection', 'device', 'customer'])->lockForUpdate()->findOrFail($ticket->id);
            if (! $ticket->inspection || ! $ticket->customer_device_id) {
                throw ValidationException::withMessages(['inspection' => 'يجب إكمال فحص الاستلام وربط جواز الجهاز قبل إنشاء عرض السعر.']);
            }

            $version = (int) $ticket->quotations()->withTrashed()->max('version') + 1;
            $items = collect($data['items'])->map(function (array $item) {
                $quantity = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];

                return [...$item, 'quantity' => $quantity, 'unit_price' => $unitPrice, 'total' => round($quantity * $unitPrice, 2)];
            });
            $partsTotal = round((float) $items->sum('total'), 2);
            $total = max(0, round($partsTotal + (float) $data['labor_cost'] - (float) $data['discount'] + (float) $data['tax'], 2));
            if ((float) $data['required_deposit'] > $total) {
                throw ValidationException::withMessages(['required_deposit' => 'المقدم لا يمكن أن يكون أكبر من إجمالي عرض السعر.']);
            }

            $snapshot = [
                'ticket_number' => $ticket->ticket_number,
                'customer' => ['name' => $ticket->customer->name, 'phone' => $ticket->customer->phone],
                'device' => ['code' => $ticket->device->internal_code, 'type' => $ticket->device->type, 'brand' => $ticket->device->brand, 'model' => $ticket->device->model],
                'fault_description' => $data['fault_description'],
                'inspection_result' => $data['inspection_result'] ?? null,
                'items' => $items->values()->all(),
                'parts_total' => $partsTotal,
                'labor_cost' => (float) $data['labor_cost'],
                'discount' => (float) $data['discount'],
                'tax' => (float) $data['tax'],
                'total' => $total,
                'required_deposit' => (float) $data['required_deposit'],
                'estimated_days' => $data['estimated_days'] ?? null,
                'warranty_terms' => $data['warranty_terms'] ?? null,
                'created_at' => now()->toIso8601String(),
            ];

            $quotation = RepairQuotation::create([
                'quotation_number' => $this->numbers->next('repair_quotation', 'RQT-'.now()->format('Y').'-'),
                'repair_ticket_id' => $ticket->id,
                'customer_device_id' => $ticket->customer_device_id,
                'version' => $version,
                'status' => 'pending_customer',
                'fault_description' => $data['fault_description'],
                'inspection_result' => $data['inspection_result'] ?? null,
                'parts_total' => $partsTotal,
                'labor_cost' => $data['labor_cost'],
                'discount' => $data['discount'],
                'tax' => $data['tax'],
                'total' => $total,
                'required_deposit' => $data['required_deposit'],
                'estimated_days' => $data['estimated_days'] ?? null,
                'warranty_terms' => $data['warranty_terms'] ?? null,
                'technician_notes' => $data['technician_notes'] ?? null,
                'snapshot' => $snapshot,
                'expires_at' => now()->addHours((int) ($data['valid_hours'] ?? 72)),
                'created_by' => $userId,
            ]);
            $quotation->items()->createMany($items->all());
            $rawToken = Str::random(64);
            $quotation->accessTokens()->create([
                'purpose' => 'repair_quotation_decision',
                'token_hash' => hash('sha256', $rawToken),
                'expires_at' => $quotation->expires_at,
                'max_uses' => 20,
            ]);

            $this->repairs->changeStatus($ticket, RepairStatus::WaitingCustomerApproval, 'تم إرسال عرض السعر للعميل');
            $this->passports->event($ticket->device, 'quotation_created', 'تم إنشاء عرض سعر للصيانة', $quotation->quotation_number, $quotation, ['total' => $total]);
            ActivityLogger::record('repair_quotation.created', $quotation, newValues: $snapshot);

            return ['quotation' => $quotation->fresh(['items', 'ticket.customer', 'device']), 'token' => $rawToken];
        });
    }

    public function resolve(string $rawToken): array
    {
        $token = PublicAccessToken::with('tokenable.ticket.customer', 'tokenable.items')->where('token_hash', hash('sha256', $rawToken))->first();
        if (! $token || $token->purpose !== 'repair_quotation_decision' || $token->revoked_at || $token->expires_at->isPast() || $token->use_count >= $token->max_uses) {
            throw ValidationException::withMessages(['link' => 'الرابط غير صالح أو انتهت صلاحيته.']);
        }

        return ['token' => $token, 'quotation' => $token->tokenable];
    }

    public function respond(string $rawToken, array $data, Request $request): RepairQuotation
    {
        return DB::transaction(function () use ($rawToken, $data, $request) {
            ['token' => $access, 'quotation' => $resolved] = $this->resolve($rawToken);
            $access = PublicAccessToken::lockForUpdate()->findOrFail($access->id);
            $quotation = RepairQuotation::with(['ticket.customer', 'device'])->lockForUpdate()->findOrFail($resolved->id);
            if ($quotation->ticket->customer->phone !== $data['phone']) {
                throw ValidationException::withMessages(['phone' => 'رقم الهاتف لا يطابق بيانات صاحب الطلب.']);
            }
            if ($quotation->status !== 'pending_customer' && $data['decision'] !== 'contact') {
                throw ValidationException::withMessages(['decision' => 'تم تسجيل قرار نهائي لهذا العرض من قبل.']);
            }

            $quotation->responses()->create([
                'decision' => $data['decision'],
                'note' => $data['note'] ?? null,
                'phone' => $data['phone'],
                'quotation_snapshot' => $quotation->snapshot,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
            ]);
            $access->increment('use_count');
            $access->update(['last_used_at' => now()]);

            if (in_array($data['decision'], ['accept', 'reject'], true)) {
                $status = $data['decision'] === 'accept' ? 'approved' : 'rejected';
                $quotation->update(['status' => $status, 'decided_at' => now()]);
                $access->update(['revoked_at' => now()]);
                $repairStatus = $data['decision'] === 'accept' ? RepairStatus::Approved : RepairStatus::CustomerRejected;
                $this->repairs->changeStatus($quotation->ticket, $repairStatus, $data['note'] ?? null);
                $this->passports->event($quotation->device, 'quotation_'.$status, $data['decision'] === 'accept' ? 'وافق العميل على عرض السعر' : 'رفض العميل عرض السعر', $data['note'] ?? null, $quotation, ['total' => (float) $quotation->total]);
            }
            ActivityLogger::record('repair_quotation.'.$data['decision'], $quotation, newValues: ['decision' => $data['decision'], 'snapshot' => $quotation->snapshot], request: $request);

            return $quotation->fresh(['ticket', 'responses']);
        });
    }
}
