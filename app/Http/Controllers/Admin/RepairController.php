<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RepairStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RepairRequest;
use App\Models\Customer;
use App\Models\RepairTicket;
use App\Models\Warehouse;
use App\Services\ActivityLogger;
use App\Services\DevicePassportService;
use App\Services\NumberSequenceService;
use App\Services\RepairService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RepairController extends Controller
{
    public function __construct(private RepairService $service, private NumberSequenceService $numbers, private DevicePassportService $passports) {}

    public function store(RepairRequest $r): JsonResponse
    {
        $d = $r->validated();
        $ticket = DB::transaction(function () use ($d, $r) {
            $customer = Customer::firstOrCreate(['phone' => $d['customer_phone']], ['name' => $d['customer'], 'created_by' => $r->user()->id]);
            $device = $this->passports->create(['customer_id' => $customer->id, 'type' => $d['device']], $r->user()->id);
            $requestedStatus = $this->status($d['status']);
            $status = $requestedStatus === RepairStatus::InProgress && $r->user()->can('repair_quotations.approve_override')
                ? RepairStatus::InProgress
                : RepairStatus::AwaitingInspection;
            $ticket = RepairTicket::create(['ticket_number' => $d['number'] ?: $this->numbers->next('repair', 'REP-'.now()->format('Y').'-'), 'customer_id' => $customer->id, 'customer_device_id' => $device->id, 'warehouse_id' => Warehouse::where('is_default', true)->firstOrFail()->id, 'device_type' => $d['device'], 'reported_problem' => $d['fault'], 'status' => $status, 'received_at' => now(), 'created_by' => $r->user()->id]);
            $ticket->statusHistory()->create(['to_status' => $ticket->status->value, 'changed_by' => $r->user()->id]);
            $this->passports->event($device, 'repair_received', 'تم استلام الجهاز للصيانة', $ticket->reported_problem, $ticket, ['ticket_number' => $ticket->ticket_number]);
            if ($status === RepairStatus::InProgress) {
                ActivityLogger::record('repair.approval_override', $ticket, newValues: ['requested_status' => $requestedStatus->value, 'reason' => 'created_by_authorized_user']);
            }

            return $ticket;
        });

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
