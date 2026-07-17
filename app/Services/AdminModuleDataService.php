<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\RepairTicket;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminModuleDataService
{
    public const META = [
        'store' => ['بيانات المحل', 'حدّث اسم المحل وبيانات التواصل والفواتير.'], 'products' => ['المنتجات والبضاعة', 'أضف المنتجات والأسعار والكميات والأرقام التسلسلية.'],
        'sales' => ['المبيعات', 'أنشئ فواتير البيع وتابع المدفوعات والمرتجعات.'], 'purchases' => ['المشتريات', 'سجّل فواتير الموردين واستلام البضاعة.'],
        'inventory' => ['المخزن', 'تابع الأرصدة وحركات المخزون والجرد.'], 'repairs' => ['الصيانة', 'أدر تذاكر الصيانة والحالات وقطع الغيار.'],
        'customers' => ['العملاء', 'بيانات العملاء وسجل المبيعات والصيانة.'], 'suppliers' => ['الموردون', 'بيانات الموردين والحسابات والمشتريات.'],
        'expenses' => ['المصروفات والخزنة', 'سجّل المصروفات وحركات النقدية والورديات.'], 'reports' => ['التقارير', 'تقارير المبيعات والأرباح والمخزون والصيانة.'],
        'team' => ['الموظفون والصلاحيات', 'إدارة الموظفين والأدوار وصلاحيات الوصول.'], 'settings' => ['الإعدادات', 'إعدادات النظام والإشعارات والنسخ الاحتياطي.'],
    ];

    public const HEADERS = [
        'products' => ['كود المنتج', 'الباركود', 'اسم المنتج', 'الماركة', 'سعر البيع', 'الكمية'], 'sales' => ['رقم الفاتورة', 'العميل', 'التاريخ', 'الإجمالي', 'الحالة'],
        'purchases' => ['رقم الفاتورة', 'المورد', 'التاريخ', 'الإجمالي', 'الحالة'], 'inventory' => ['المخزن', 'المنتج', 'الرصيد الحالي', 'حد الطلب', 'الحالة'],
        'repairs' => ['رقم التذكرة', 'العميل', 'الجهاز', 'العطل', 'الحالة'], 'customers' => ['اسم العميل', 'رقم الهاتف', 'البريد الإلكتروني', 'السجل', 'الحالة'],
        'suppliers' => ['اسم المورد', 'رقم الهاتف', 'الرصيد', 'حالة الحساب'], 'expenses' => ['رقم الحركة', 'البيان', 'التاريخ', 'القيمة', 'النوع'],
        'reports' => ['اسم التقرير', 'النطاق', 'الإجمالي', 'الإجراء', 'الحالة'], 'team' => ['اسم الموظف', 'البريد الإلكتروني', 'الدور', 'الصلاحيات', 'الحالة'],
        'settings' => ['القسم', 'الإعداد', 'القيمة', 'الإجراء', 'الحالة'],
    ];

    public const ADD_LABELS = ['store' => 'تعديل بيانات المحل', 'products' => 'إضافة منتج', 'sales' => 'فاتورة بيع جديدة', 'purchases' => 'فاتورة شراء جديدة', 'inventory' => 'إضافة حركة مخزون', 'repairs' => 'تذكرة صيانة جديدة', 'customers' => 'إضافة عميل', 'suppliers' => 'إضافة مورد', 'expenses' => 'إضافة حركة مالية', 'reports' => 'إنشاء تقرير', 'team' => 'إضافة موظف', 'settings' => 'إضافة إعداد'];

    public function rows(string $module): array
    {
        return match ($module) {
            'products' => Product::with(['brand', 'stocks'])->latest()->paginate(25)->through(fn ($x) => $this->row($x->id, [$x->sku, $x->barcode ?? '—', $x->name, $x->brand?->name ?? '—', number_format((float) $x->sale_price, 2), number_format((float) $x->stocks->sum('quantity'), 3)], $x->image_path ? Storage::disk('public')->url($x->image_path) : null))->items(),
            'sales' => Sale::with('customer')->latest()->paginate(25)->through(fn ($x) => $this->row($x->id, [$x->invoice_number, $x->customer?->name ?? 'عميل نقدي', $x->created_at->format('Y-m-d'), number_format((float) $x->total, 2), $x->status]))->items(),
            'purchases' => Purchase::with('supplier')->latest()->paginate(25)->through(fn ($x) => $this->row($x->id, [$x->invoice_number, $x->supplier?->name ?? '—', $x->created_at->format('Y-m-d'), number_format((float) $x->total, 2), $x->status]))->items(),
            'inventory' => WarehouseStock::with(['warehouse', 'product'])->latest()->paginate(25)->through(fn ($x) => $this->row($x->id, [$x->warehouse->name, $x->product->name, $x->quantity, $x->product->reorder_level, (float) $x->quantity <= $x->product->reorder_level ? 'منخفض' : 'متوفر']))->items(),
            'repairs' => RepairTicket::with('customer')->latest()->paginate(25)->through(fn ($x) => $this->row($x->id, [$x->ticket_number, $x->customer->name, trim($x->brand.' '.$x->model), $x->reported_problem, $x->status->value]))->items(),
            'customers' => Customer::withCount(['sales', 'repairs'])->latest()->paginate(25)->through(fn ($x) => $this->row($x->id, [$x->name, $x->phone, $x->email ?? '—', "{$x->sales_count} فواتير / {$x->repairs_count} صيانات", $x->is_active ? 'نشط' : 'موقوف']))->items(),
            'suppliers' => Supplier::withSum('purchases', 'due_amount')->latest()->paginate(25)->through(fn ($x) => $this->row($x->id, [$x->name, $x->phone ?? '—', number_format((float) ($x->purchases_sum_due_amount ?? 0), 2), (float) ($x->purchases_sum_due_amount ?? 0) > 0 ? 'مستحق' : 'مسدد']))->items(),
            'expenses' => DB::table('expenses')->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')->select('expenses.*', 'expense_categories.name as category')->latest('expenses.created_at')->limit(25)->get()->map(fn ($x) => $this->row($x->id, [$x->expense_number, $x->description, $x->expense_date, number_format((float) $x->amount, 2), $x->category]))->all(),
            'reports' => $this->reportRows(),
            'team' => User::with(['roles', 'permissions'])->where('is_staff', true)->latest()->paginate(25)->through(fn ($x) => $this->row($x->id, [$x->name, $x->email, $x->roles->pluck('name')->join('، ') ?: '—', $x->getAllPermissions()->pluck('name')->join('، ') ?: '—', $x->is_active && ! $x->suspended_at ? 'نشط' : 'موقوف']))->items(),
            'settings' => DB::table('settings')->orderBy('group')->orderBy('key')->get()->map(fn ($x) => $this->row($x->id, [$x->group, $x->key, $x->value, 'تعديل', 'فعال']))->all(),
            default => [],
        };
    }

    private function row(int $id, array $cells, ?string $imageUrl = null): array
    {
        return array_filter(['id' => $id, 'cells' => $cells, 'image_url' => $imageUrl], fn ($value) => $value !== null);
    }

    private function reportRows(): array
    {
        return [
            $this->row(1, ['إجمالي المبيعات', 'كل الفواتير', number_format((float) Sale::where('status', 'finalized')->sum('total'), 2), 'عرض التقرير', 'محدث']),
            $this->row(2, ['قيمة المخزون', 'كل المخازن', number_format((float) WarehouseStock::sum(DB::raw('quantity * average_cost')), 2), 'عرض التقرير', 'محدث']),
            $this->row(3, ['تذاكر الصيانة المفتوحة', 'الحالية', RepairTicket::whereNotIn('status', ['delivered', 'cancelled'])->count(), 'عرض التقرير', 'محدث']),
        ];
    }
}
