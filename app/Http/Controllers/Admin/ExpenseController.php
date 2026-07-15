<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExpenseRequest;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\ActivityLogger;
use App\Services\NumberSequenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function __construct(private NumberSequenceService $numbers) {}

    public function store(ExpenseRequest $r): JsonResponse
    {
        return DB::transaction(function () use ($r) {
            $d = $r->validated();
            $category = ExpenseCategory::firstOrCreate(['name' => $d['type']], ['is_active' => true]);
            $register = CashRegister::where('is_active', true)->first();
            $expense = Expense::create(['expense_number' => $d['number'] ?: $this->numbers->next('expense', 'EXP-'.now()->format('Y').'-'), 'expense_category_id' => $category->id, 'cash_register_id' => $register?->id, 'amount' => $d['amount'], 'expense_date' => $d['date'], 'description' => $d['description'], 'created_by' => $r->user()->id]);
            if ($register) {
                CashTransaction::create(['cash_register_id' => $register->id, 'type' => 'expense', 'amount' => -(float) $d['amount'], 'reference_type' => $expense->getMorphClass(), 'reference_id' => $expense->id, 'description' => $d['description'], 'created_by' => $r->user()->id]);
            }ActivityLogger::record('expense.created', $expense);

            return response()->json(['message' => 'تم تسجيل المصروف وحركة الخزنة.', 'row' => $this->row($expense)], 201);
        });
    }

    public function update(ExpenseRequest $r, Expense $expense): JsonResponse
    {
        $old = $expense->toArray();
        $d = $r->validated();
        $category = ExpenseCategory::firstOrCreate(['name' => $d['type']], ['is_active' => true]);
        $expense->update(['expense_number' => $d['number'] ?: $expense->expense_number, 'expense_category_id' => $category->id, 'amount' => $d['amount'], 'expense_date' => $d['date'], 'description' => $d['description']]);
        ActivityLogger::record('expense.updated', $expense, $old, $expense->fresh()->toArray());

        return response()->json(['message' => 'تم تعديل المصروف.', 'row' => $this->row($expense)]);
    }

    public function destroy(Expense $expense): JsonResponse
    {
        abort_unless(request()->user()->can('manage-expenses'), 403);
        $expense->delete();
        ActivityLogger::record('expense.deleted', $expense);

        return response()->json(['message' => 'تم حذف المصروف مع الاحتفاظ بسجل النشاط.']);
    }

    private function row(Expense $e): array
    {
        $e->load('category');

        return ['id' => $e->id, 'cells' => [$e->expense_number, $e->description, $e->expense_date->format('Y-m-d'), number_format((float) $e->amount, 2), $e->category->name]];
    }
}
