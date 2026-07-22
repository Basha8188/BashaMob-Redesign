<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RepairQuotation;
use App\Models\RepairTicket;
use App\Services\QrCodeService;
use App\Services\RepairQuotationService;
use Illuminate\Http\Request;

class RepairQuotationController extends Controller
{
    public function __construct(private RepairQuotationService $quotations, private QrCodeService $qr) {}

    public function create(RepairTicket $repair)
    {
        $repair->load(['customer', 'device', 'inspection', 'quotations']);

        return view('admin.repairs.quotation-create', compact('repair'));
    }

    public function store(Request $request, RepairTicket $repair)
    {
        $data = $request->validate([
            'fault_description' => ['required', 'string', 'max:3000'],
            'inspection_result' => ['nullable', 'string', 'max:3000'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'gte:0'],
            'labor_cost' => ['required', 'numeric', 'gte:0'],
            'discount' => ['required', 'numeric', 'gte:0'],
            'tax' => ['required', 'numeric', 'gte:0'],
            'required_deposit' => ['required', 'numeric', 'gte:0'],
            'estimated_days' => ['nullable', 'integer', 'between:1,365'],
            'warranty_terms' => ['nullable', 'string', 'max:5000'],
            'technician_notes' => ['nullable', 'string', 'max:3000'],
            'valid_hours' => ['nullable', 'integer', 'between:1,720'],
        ]);
        $result = $this->quotations->create($repair, $data, $request->user()->id);
        session()->flash('quotation_public_token_'.$result['quotation']->id, $result['token']);

        return redirect()->route('admin.repairs.quotations.show', $result['quotation'])->with('status', 'تم إنشاء عرض السعر والرابط الآمن.');
    }

    public function show(RepairQuotation $quotation)
    {
        $quotation->load(['ticket.customer', 'device', 'items', 'responses']);
        $token = session('quotation_public_token_'.$quotation->id);
        $publicUrl = $token ? route('repair-quotations.public.show', ['token' => $token]) : null;

        return view('admin.repairs.quotation-show', compact('quotation', 'publicUrl') + ['qrCode' => $publicUrl ? $this->qr->dataUri($publicUrl) : null]);
    }
}
