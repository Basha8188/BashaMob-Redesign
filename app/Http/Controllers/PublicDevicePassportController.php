<?php

namespace App\Http\Controllers;

use App\Models\CustomerDevice;

class PublicDevicePassportController extends Controller
{
    public function __invoke(string $code)
    {
        abort_unless(request()->hasValidSignature(), 403);
        $device = CustomerDevice::with(['repairs:id,customer_device_id,ticket_number,status,received_at', 'productSerial.product'])->where('internal_code', $code)->firstOrFail();

        return view('public.device-passport', compact('device'));
    }
}
