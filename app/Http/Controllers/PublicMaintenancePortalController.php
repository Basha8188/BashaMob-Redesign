<?php

namespace App\Http\Controllers;

use App\Models\RepairTicket;

class PublicMaintenancePortalController extends Controller
{
    public function __invoke(string $ticket)
    {
        abort_unless(request()->hasValidSignature(), 403);
        $repair = RepairTicket::with(['device', 'statusHistory'])->where('ticket_number', $ticket)->firstOrFail();

        return view('public.maintenance-status', compact('repair'));
    }
}
