<?php

namespace App\Services;

use App\Enums\RepairStatus;
use App\Models\RepairInspection;
use App\Models\RepairTicket;
use Illuminate\Support\Facades\DB;

class RepairInspectionService
{
    public function __construct(private RepairService $repairs, private DevicePassportService $passports) {}

    public function save(RepairTicket $ticket, array $data, array $photos, int $userId): RepairInspection
    {
        return DB::transaction(function () use ($ticket, $data, $photos, $userId) {
            $ticket = RepairTicket::with('device')->lockForUpdate()->findOrFail($ticket->id);
            abort_unless($ticket->customer_device_id, 422, 'يجب ربط التذكرة بجواز جهاز أولًا.');
            $inspection = RepairInspection::withTrashed()->firstOrNew(['repair_ticket_id' => $ticket->id]);
            $before = $inspection->exists ? $inspection->toArray() : [];
            if ($inspection->trashed()) {
                $inspection->restore();
            }
            $inspection->fill([
                ...$data,
                'customer_device_id' => $ticket->customer_device_id,
                'inspected_by' => $userId,
                'completed_at' => now(),
            ])->save();

            foreach ($photos as $photo) {
                $inspection->photos()->create([...$photo, 'uploaded_by' => $userId]);
            }

            $this->repairs->changeStatus($ticket, RepairStatus::AwaitingQuotation, 'اكتمل فحص الاستلام');
            $this->passports->event($ticket->device, 'repair_inspected', 'اكتمل فحص استلام الجهاز', $ticket->ticket_number, $inspection, ['ticket_number' => $ticket->ticket_number]);
            ActivityLogger::record($before ? 'repair_inspection.updated' : 'repair_inspection.created', $inspection, $before, $inspection->fresh()->toArray());

            return $inspection->fresh(['ticket.customer', 'device', 'photos', 'inspector']);
        });
    }
}
