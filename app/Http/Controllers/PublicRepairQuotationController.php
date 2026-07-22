<?php

namespace App\Http\Controllers;

use App\Services\RepairQuotationService;
use Illuminate\Http\Request;

class PublicRepairQuotationController extends Controller
{
    public function __construct(private RepairQuotationService $quotations) {}

    public function show(string $token)
    {
        return view('public.repair-quotation', $this->quotations->resolve($token) + compact('token'));
    }

    public function respond(Request $request, string $token)
    {
        $data = $request->validate(['decision' => ['required', 'in:accept,reject,contact'], 'phone' => ['required', 'string', 'max:24'], 'note' => ['nullable', 'required_if:decision,reject', 'string', 'max:2000']]);
        $quotation = $this->quotations->respond($token, $data, $request);

        return back()->with('status', match ($data['decision']) {
            'accept' => 'تم تسجيل موافقتك بنجاح.','reject' => 'تم تسجيل رفض العرض.','contact' => 'تم تسجيل طلب التواصل معك.'
        });
    }
}
