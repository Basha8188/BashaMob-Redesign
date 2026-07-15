<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashRegisterController extends Controller
{
    public function open(Request $r, CashRegister $cashRegister): JsonResponse
    {
        abort_unless($r->user()->can('manage-expenses'), 403);
        $d = $r->validate(['opening_balance' => ['required', 'numeric', 'min:0']]);
        if ($cashRegister->sessions()->where('status', 'open')->exists()) {
            throw ValidationException::withMessages(['register' => 'الخزنة مفتوحة بالفعل.']);
        }$session = $cashRegister->sessions()->create(['user_id' => $r->user()->id, 'opening_balance' => $d['opening_balance'], 'status' => 'open', 'opened_at' => now()]);
        ActivityLogger::record('cash_session.opened', $session);

        return response()->json(['message' => 'تم فتح الوردية.', 'session' => $session]);
    }

    public function close(Request $r, CashSession $cashSession): JsonResponse
    {
        abort_unless($r->user()->can('manage-expenses'), 403);
        $d = $r->validate(['closing_balance' => ['required', 'numeric', 'min:0']]);

        return DB::transaction(function () use ($cashSession, $d) {
            $cashSession = CashSession::lockForUpdate()->findOrFail($cashSession->id);
            if ($cashSession->status !== 'open') {
                throw ValidationException::withMessages(['session' => 'الوردية مغلقة بالفعل.']);
            }$net = (float) DB::table('cash_transactions')->where('cash_session_id', $cashSession->id)->sum('amount');
            $cashSession->update(['closing_balance' => $d['closing_balance'], 'expected_balance' => (float) $cashSession->opening_balance + $net, 'status' => 'closed', 'closed_at' => now()]);
            ActivityLogger::record('cash_session.closed', $cashSession);

            return response()->json(['message' => 'تم إغلاق الوردية.', 'session' => $cashSession]);
        });
    }
}
