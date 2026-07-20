<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerOrder;
use App\Models\MaintenanceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerRequestController extends Controller
{
    public function update(Request $request, string $type, int $id): JsonResponse
    {
        abort_unless($request->user()->can('manage-customers'), 403);
        $data = $request->validate(['decision' => ['required', 'in:approved,rejected']]);
        $record = $type === 'maintenance'
            ? MaintenanceRequest::findOrFail($id)
            : ($type === 'order' ? CustomerOrder::findOrFail($id) : abort(404));
        $approved = $data['decision'] === 'approved';
        $message = $approved
            ? 'تمت الموافقة على طلبك، برجاء التوجه إلى المحل لإتمام عملية '.($type === 'maintenance' ? 'الصيانة.' : 'الشراء.')
            : 'نعتذر، تعذر قبول الطلب حاليًا. يمكنك التواصل مع المحل لمعرفة التفاصيل.';
        $record->update(['status' => $approved ? 'approved' : 'rejected', 'review_message' => $message, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);

        return response()->json(['message' => $message, 'status' => $record->status]);
    }
}
