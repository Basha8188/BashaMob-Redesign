<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RepairTicket;
use App\Services\QrCodeService;
use App\Services\RepairInspectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\File;

class RepairInspectionController extends Controller
{
    public function __construct(private RepairInspectionService $inspections, private QrCodeService $qr) {}

    public function create(RepairTicket $repair)
    {
        $repair->load(['customer', 'device', 'inspection.photos']);
        abort_unless($repair->customer_device_id, 422, 'اربط التذكرة بجواز الجهاز أولًا.');

        return view('admin.repairs.inspection', compact('repair'));
    }

    public function store(Request $request, RepairTicket $repair)
    {
        if (is_string($request->input('damage_map'))) {
            $request->merge(['damage_map' => json_decode($request->input('damage_map'), true) ?: []]);
        }
        $data = $request->validate([
            'has_google_account' => ['nullable', 'boolean'],
            'has_icloud_account' => ['nullable', 'boolean'],
            'is_locked' => ['nullable', 'boolean'],
            'lock_type' => ['nullable', 'string', 'max:60'],
            'accessories' => ['nullable', 'array'],
            'accessories.*' => ['string', 'max:80'],
            'damage_map' => ['nullable', 'array', 'max:30'],
            'damage_map.*.x' => ['required', 'numeric', 'between:0,100'],
            'damage_map.*.y' => ['required', 'numeric', 'between:0,100'],
            'damage_map.*.type' => ['required', 'in:scratch,crack,dent,other'],
            'employee_notes' => ['nullable', 'string', 'max:3000'],
            'terms_snapshot' => ['nullable', 'string', 'max:5000'],
            'customer_consent' => ['required', 'in:approved,pending'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(8 * 1024)],
            'photo_types' => ['nullable', 'array'],
            'photo_types.*' => ['nullable', 'in:front,back,screen,damage,accessories,after'],
        ]);

        $photos = [];
        foreach ($request->file('photos', []) as $index => $file) {
            $photos[] = [
                'type' => $data['photo_types'][$index] ?? 'damage',
                'disk' => 'public',
                'path' => $file->store('repair-inspections', 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'sort_order' => $index,
            ];
        }
        unset($data['photos'], $data['photo_types']);
        $data['has_google_account'] = (bool) ($data['has_google_account'] ?? false);
        $data['has_icloud_account'] = (bool) ($data['has_icloud_account'] ?? false);
        $data['is_locked'] = (bool) ($data['is_locked'] ?? false);
        $data['consented_at'] = $data['customer_consent'] === 'approved' ? now() : null;

        try {
            $inspection = $this->inspections->save($repair, $data, $photos, $request->user()->id);
        } catch (\Throwable $exception) {
            foreach ($photos as $photo) {
                Storage::disk($photo['disk'])->delete($photo['path']);
            }
            throw $exception;
        }

        return redirect()->route('admin.repairs.inspections.receipt', $repair)->with('status', 'تم حفظ فحص الاستلام.');
    }

    public function receipt(RepairTicket $repair)
    {
        $repair->load(['customer', 'device', 'inspection.photos', 'inspection.inspector']);
        abort_unless($repair->inspection, 404);
        $trackingUrl = URL::temporarySignedRoute('maintenance.portal', now()->addDays(30), ['ticket' => $repair->ticket_number]);

        return view('admin.repairs.receipt', compact('repair', 'trackingUrl') + ['qrCode' => $this->qr->dataUri($trackingUrl)]);
    }
}
