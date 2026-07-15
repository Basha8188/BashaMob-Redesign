<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RepairStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RepairRequest;
use App\Models\Customer;
use App\Models\RepairTicket;
use App\Models\Warehouse;
use App\Services\NumberSequenceService;
use App\Services\RepairService;
use Illuminate\Http\JsonResponse;

class RepairController extends Controller
{
    public function __construct(private RepairService $service, private NumberSequenceService $numbers) {}

    public function store(RepairRequest $r): JsonResponse
    {
        $d = $r->validated();
        $customer = Customer::firstOrCreate(['phone' => $d['customer_phone']], ['name' => $d['customer'], 'created_by' => $r->user()->id]);
        $ticket = RepairTicket::create(['ticket_number' => $d['number'] ?: $this->numbers->next('repair', 'REP-'.now()->format('Y').'-'), 'customer_id' => $customer->id, 'warehouse_id' => Warehouse::where('is_default', true)->firstOrFail()->id, 'device_type' => $d['device'], 'reported_problem' => $d['fault'], 'status' => $this->status($d['status']), 'received_at' => now(), 'created_by' => $r->user()->id]);
        $ticket->statusHistory()->create(['to_status' => $ticket->status->value, 'changed_by' => $r->user()->id]);

        return response()->json(['message' => 'تم إنشاء تذكرة الصيانة.', 'row' => $this->row($ticket)], 201);
    }

    public function update(RepairRequest $r, RepairTicket $repair): JsonResponse
    {
        $d = $r->validated();
        $repair->update(['device_type' => $d['device'], 'reported_problem' => $d['fault']]);
        $repair = $this->service->changeStatus($repair, $this->status($d['status']));

        return response()->json(['message' => 'تم تحديث الصيانة وتسجيل الحالة.', 'row' => $this->row($repair)]);
    }

    private function status(string $s): RepairStatus
    {
        return match ($s) {
            'قيد الإصلاح','In repair' => RepairStatus::InProgress,'بانتظار قطعة','Waiting for part' => RepairStatus::WaitingParts,'جاهز للاستلام','Ready for pickup' => RepairStatus::ReadyForDelivery,'تم التسليم','Delivered' => RepairStatus::Delivered,default => RepairStatus::Received
        };
    }

    private function row(RepairTicket $t): array
    {
        $t->load('customer');

        return ['id' => $t->id, 'cells' => [$t->ticket_number, $t->customer->name, $t->device_type, $t->reported_problem, $t->status->value]];
    }
}
