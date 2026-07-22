<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerDevice;
use App\Services\DevicePassportService;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class DevicePassportController extends Controller
{
    public function __construct(private DevicePassportService $passports, private QrCodeService $qr) {}

    public function index(Request $request)
    {
        return view('admin.device-passports.index', [
            'devices' => $this->passports->search($request->string('q')->toString()),
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->limit(200)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateDevice($request);
        $device = $this->passports->create($data, $request->user()->id);

        return redirect()->route('admin.device-passports.show', $device)->with('status', 'تم إنشاء جواز سفر الجهاز.');
    }

    public function show(CustomerDevice $device)
    {
        $device = $this->passports->details($device);
        $verifyUrl = URL::signedRoute('device-passports.verify', ['code' => $device->internal_code]);

        return view('admin.device-passports.show', compact('device', 'verifyUrl') + ['qrCode' => $this->qr->dataUri($verifyUrl)]);
    }

    public function update(Request $request, CustomerDevice $device)
    {
        $data = $this->validateDevice($request, $device);
        $this->passports->update($device, $data, $request->user()->id);

        return back()->with('status', 'تم تحديث جواز سفر الجهاز.');
    }

    private function validateDevice(Request $request, ?CustomerDevice $device = null): array
    {
        return $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'type' => ['required', 'string', 'max:40'],
            'brand' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:60'],
            'storage' => ['nullable', 'string', 'max:60'],
            'imei_1' => ['nullable', 'string', 'max:30', Rule::unique('customer_devices')->ignore($device)],
            'imei_2' => ['nullable', 'string', 'max:30', Rule::unique('customer_devices')->ignore($device)],
            'serial_number' => ['nullable', 'string', 'max:100', Rule::unique('customer_devices')->ignore($device)],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
    }
}
