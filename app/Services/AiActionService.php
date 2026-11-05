<?php

namespace App\Services;

use App\Enums\ProductSerialStatus;
use App\Enums\ProductTrackingType;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AiActionService
{
    public function __construct(
        private SaleService $sales,
        private NumberSequenceService $numbers,
    ) {}

    public function toolsFor(?User $user): array
    {
        if (! $user?->can('create-sales')) {
            return [];
        }

        return [[
            'type' => 'function',
            'name' => 'prepare_sales_invoice',
            'description' => 'Prepare, but do not create, a sales invoice. Use this when an authenticated employee asks to sell a product or create a sales invoice. The application will ask the employee to confirm before any data is changed.',
            'strict' => true,
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'product_query' => ['type' => 'string', 'description' => 'Product name, model, SKU, barcode, or brand mentioned by the employee.'],
                    'quantity' => ['type' => 'number', 'description' => 'Quantity to sell.'],
                    'unit_price' => ['type' => ['number', 'null'], 'description' => 'Explicit unit price, or null to use the catalog sale price.'],
                    'payment_method' => ['type' => 'string', 'enum' => ['cash', 'card', 'bank_transfer', 'credit']],
                    'customer_name' => ['type' => ['string', 'null']],
                    'customer_phone' => ['type' => ['string', 'null']],
                    'serial_number' => ['type' => ['string', 'null'], 'description' => 'IMEI or serial number when the product is individually tracked, otherwise null.'],
                ],
                'required' => ['product_query', 'quantity', 'unit_price', 'payment_method', 'customer_name', 'customer_phone', 'serial_number'],
                'additionalProperties' => false,
            ],
        ]];
    }

    public function canHandleLocally(?User $user, string $question): bool
    {
        return $user?->can('create-sales')
            && (bool) preg_match('/(?:فاتور(?:ة|ه)\s*بيع|بيع\s+\d|sales?\s+invoice|sell\s+\d)/ui', $question);
    }

    public function prepareSaleFromText(User $user, string $question, string $locale = 'ar'): array
    {
        abort_unless($user->can('create-sales'), 403);

        $products = Product::query()->with('brand')->where('is_active', true)->get();
        $matches = $products->filter(function (Product $product) use ($question) {
            return mb_stripos($question, $product->name) !== false
                || mb_stripos($question, $product->sku) !== false
                || ($product->barcode && mb_stripos($question, $product->barcode) !== false);
        });

        if ($matches->count() === 1) {
            $productQuery = $matches->first()->sku;
        } elseif ($matches->count() > 1) {
            $names = $matches->map(fn (Product $product) => "{$product->name} ({$product->sku})")->implode('، ');

            return $this->failure($locale, "وجدت أكثر من منتج مذكور في الأمر: {$names}. حدد منتجًا واحدًا فقط.", "The command mentions more than one product: {$names}. Specify exactly one product.");
        } else {
            $brands = $products->pluck('brand')->filter()->unique('id')
                ->filter(fn ($brand) => mb_stripos($question, $brand->name) !== false);
            if ($brands->count() !== 1) {
                return $this->failure($locale, 'حدد اسم المنتج الكامل أو SKU داخل أمر الفاتورة.', 'Include the exact product name or SKU in the invoice command.');
            }
            $productQuery = $brands->first()->name;
        }

        $normalized = strtr($question, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
        $quantity = 1.0;
        if (preg_match('/(?:فاتور(?:ة|ه)\s*بيع|بيع|عدد|quantity|qty)\s*[:=]?\s*([0-9]+(?:[.,][0-9]+)?)/ui', $normalized, $quantityMatch)) {
            $quantity = (float) str_replace(',', '.', $quantityMatch[1]);
        }
        $unitPrice = null;
        if (preg_match('/(?:بسعر|سعر(?:ه|ها)?|price(?:d)?(?:\s+at)?)\s*[:=]?\s*([0-9]+(?:[.,][0-9]+)?)/ui', $normalized, $priceMatch)) {
            $unitPrice = (float) str_replace(',', '', $priceMatch[1]);
        }

        $paymentMethod = match (true) {
            (bool) preg_match('/(?:آجل|اجل|credit)/ui', $question) => 'credit',
            (bool) preg_match('/(?:بطاق(?:ة|ه)|كارت|card)/ui', $question) => 'card',
            (bool) preg_match('/(?:تحويل|bank\s*transfer)/ui', $question) => 'bank_transfer',
            default => 'cash',
        };
        $serialNumber = null;
        if (preg_match('/(?:imei|serial|سيريال)\s*[:#-]?\s*([a-z0-9-]{6,100})/ui', $normalized, $serialMatch)) {
            $serialNumber = $serialMatch[1];
        }

        return $this->prepareSale($user, [
            'product_query' => $productQuery,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'payment_method' => $paymentMethod,
            'customer_name' => null,
            'customer_phone' => null,
            'serial_number' => $serialNumber,
        ], $locale);
    }

    public function prepareSale(User $user, array $arguments, string $locale = 'ar'): array
    {
        abort_unless($user->can('create-sales'), 403);

        $data = Validator::make($arguments, [
            'product_query' => ['required', 'string', 'max:150'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'unit_price' => ['nullable', 'numeric', 'gte:0', 'max:999999999'],
            'payment_method' => ['required', 'in:cash,card,bank_transfer,credit'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:24'],
            'serial_number' => ['nullable', 'string', 'max:100'],
        ])->validate();

        $warehouse = Warehouse::query()->where('is_default', true)->where('is_active', true)->first();
        if (! $warehouse) {
            return $this->failure($locale, 'لا يوجد مخزن افتراضي نشط. حدده من إعدادات المخازن أولًا.', 'There is no active default warehouse. Configure one before creating an invoice.');
        }

        $query = trim($data['product_query']);
        $matches = Product::query()
            ->with('brand')
            ->where('is_active', true)
            ->where(function ($builder) use ($query) {
                $builder->where('sku', $query)
                    ->orWhere('barcode', $query)
                    ->orWhere('name', $query)
                    ->orWhere('name', 'like', "%{$query}%")
                    ->orWhereHas('brand', fn ($brand) => $brand->where('name', 'like', "%{$query}%"));
            })
            ->limit(6)
            ->get();

        if ($matches->isEmpty()) {
            return $this->failure($locale, "لم أجد منتجًا مطابقًا لـ «{$query}». اكتب اسمًا أدق أو كود SKU.", "I could not find a product matching “{$query}”. Use a more specific name or SKU.");
        }

        $exact = $matches->filter(fn (Product $product) => strcasecmp($product->sku, $query) === 0
            || ($product->barcode && strcasecmp($product->barcode, $query) === 0)
            || strcasecmp($product->name, $query) === 0
        );

        if ($exact->count() === 1) {
            $product = $exact->first();
        } elseif ($matches->count() === 1) {
            $product = $matches->first();
        } else {
            $choices = $matches->map(fn (Product $product) => "{$product->name} ({$product->sku})")->implode('، ');

            return $this->failure(
                $locale,
                "وجدت أكثر من منتج مطابق: {$choices}. اكتب اسم المنتج الكامل أو SKU حتى لا أنشئ فاتورة لمنتج خاطئ.",
                "I found more than one match: {$choices}. Specify the full product name or SKU to avoid invoicing the wrong item."
            );
        }

        $serial = null;
        if ($product->tracking_type !== ProductTrackingType::Quantity) {
            if (! $data['serial_number']) {
                return $this->failure($locale, "منتج {$product->name} يُتتبّع بالـ IMEI/Serial. أرسل الرقم المطلوب بيعه.", "{$product->name} is IMEI/serial tracked. Provide the unit's IMEI or serial number.");
            }

            $serial = ProductSerial::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->where('status', ProductSerialStatus::Available)
                ->where(function ($builder) use ($data) {
                    $builder->where('imei_1', $data['serial_number'])
                        ->orWhere('imei_2', $data['serial_number'])
                        ->orWhere('serial_number', $data['serial_number']);
                })->first();

            if (! $serial) {
                return $this->failure($locale, 'رقم IMEI/Serial غير موجود أو غير متاح للبيع في المخزن الافتراضي.', 'That IMEI/serial is not available for sale in the default warehouse.');
            }

            if ((float) $data['quantity'] !== 1.0) {
                return $this->failure($locale, 'الفاتورة ذات IMEI/Serial تقبل وحدة واحدة لكل رقم. ابدأ بوحدة واحدة.', 'An IMEI/serial identifies one unit. Use a quantity of one.');
            }
        }

        $stock = WarehouseStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->whereNull('product_variant_id')
            ->first();
        $available = $stock ? (float) $stock->quantity - (float) $stock->reserved_quantity : 0;
        if ($available < (float) $data['quantity']) {
            return $this->failure($locale, "المخزون المتاح من {$product->name} هو {$available} فقط، لذلك لن أنشئ الفاتورة.", "Only {$available} units of {$product->name} are available, so I will not prepare this invoice.");
        }

        $unitPrice = $data['unit_price'] === null ? (float) $product->sale_price : (float) $data['unit_price'];
        $quantity = (float) $data['quantity'];
        $total = round($quantity * $unitPrice, 2);
        $token = (string) Str::uuid();
        $customerName = $data['customer_name'] ?: ($locale === 'en' ? 'Cash customer' : 'عميل نقدي');
        $customerPhone = $data['customer_phone'] ?: 'CASH-CUSTOMER';
        $paymentLabels = [
            'cash' => $locale === 'en' ? 'Cash' : 'نقدي',
            'card' => $locale === 'en' ? 'Card' : 'بطاقة',
            'bank_transfer' => $locale === 'en' ? 'Bank transfer' : 'تحويل بنكي',
            'credit' => $locale === 'en' ? 'Credit' : 'آجل',
        ];

        Cache::put($this->cacheKey($token), [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'product_serial_id' => $serial?->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'payment_method' => $data['payment_method'],
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'product_query' => $query,
        ], now()->addMinutes(10));

        $priceChanged = abs($unitPrice - (float) $product->sale_price) > 0.009;
        $answer = $locale === 'en'
            ? 'I prepared the invoice below. Review it and confirm; no stock or financial data has changed yet.'
            : 'جهزت الفاتورة بالبيانات التالية. راجعها واضغط تأكيد؛ لم يتم تغيير المخزون أو تسجيل أي حركة مالية حتى الآن.';

        return [
            'answer' => $answer,
            'action' => [
                'type' => 'confirmation',
                'token' => $token,
                'confirm_url' => route('assistant.actions.confirm', ['token' => $token]),
                'title' => $locale === 'en' ? 'Confirm sales invoice' : 'تأكيد فاتورة البيع',
                'details' => [
                    ['label' => $locale === 'en' ? 'Product' : 'المنتج', 'value' => "{$product->name} — {$product->sku}"],
                    ['label' => $locale === 'en' ? 'Quantity' : 'الكمية', 'value' => (string) $quantity],
                    ['label' => $locale === 'en' ? 'Unit price' : 'سعر الوحدة', 'value' => number_format($unitPrice, 2).' '.($locale === 'en' ? 'EGP' : 'ج.م')],
                    ['label' => $locale === 'en' ? 'Total' : 'الإجمالي', 'value' => number_format($total, 2).' '.($locale === 'en' ? 'EGP' : 'ج.م')],
                    ['label' => $locale === 'en' ? 'Payment' : 'الدفع', 'value' => $paymentLabels[$data['payment_method']]],
                    ['label' => $locale === 'en' ? 'Customer' : 'العميل', 'value' => $customerName],
                ],
                'warning' => $priceChanged
                    ? ($locale === 'en' ? 'The requested price differs from the catalog price of '.number_format((float) $product->sale_price, 2).' EGP.' : 'السعر المطلوب مختلف عن سعر المنتج المسجل وهو '.number_format((float) $product->sale_price, 2).' ج.م.')
                    : null,
                'confirm_label' => $locale === 'en' ? 'Confirm and create' : 'تأكيد وإنشاء الفاتورة',
                'cancel_label' => $locale === 'en' ? 'Cancel' : 'إلغاء',
            ],
        ];
    }

    public function confirmSale(User $user, string $token, string $locale = 'ar'): array
    {
        return Cache::lock("ai-action-lock:{$token}", 15)->block(3, function () use ($user, $token, $locale) {
            $payload = Cache::get($this->cacheKey($token));
            if (! $payload || (int) $payload['user_id'] !== (int) $user->id) {
                throw ValidationException::withMessages(['action' => $locale === 'en' ? 'This action expired or was already completed.' : 'انتهت صلاحية هذا الإجراء أو تم تنفيذه بالفعل.']);
            }

            abort_unless($user->can('create-sales'), 403);

            $sale = DB::transaction(function () use ($payload, $user, $token) {
                $product = Product::query()->where('is_active', true)->findOrFail($payload['product_id']);
                $warehouse = Warehouse::query()->where('is_active', true)->findOrFail($payload['warehouse_id']);
                $customer = Customer::withTrashed()->where('phone', $payload['customer_phone'])->first();
                if ($customer?->trashed()) {
                    $customer->restore();
                }
                $customer ??= Customer::create([
                    'phone' => $payload['customer_phone'],
                    'name' => $payload['customer_name'],
                    'created_by' => $user->id,
                ]);

                $total = round((float) $payload['quantity'] * (float) $payload['unit_price'], 2);
                $sale = Sale::create([
                    'invoice_number' => $this->numbers->next('sale', 'SAL-'.now()->format('Y').'-'),
                    'idempotency_key' => $token,
                    'customer_id' => $customer->id,
                    'warehouse_id' => $warehouse->id,
                    'cashier_id' => $user->id,
                    'status' => 'draft',
                    'subtotal' => $total,
                    'total' => $total,
                    'due_amount' => $total,
                    'notes' => 'Created through BashaMob AI assistant',
                ]);
                $sale->items()->create([
                    'product_id' => $product->id,
                    'product_serial_id' => $payload['product_serial_id'],
                    'product_variant_id' => $payload['product_serial_id'] ? ProductSerial::findOrFail($payload['product_serial_id'])->product_variant_id : null,
                    'quantity' => $payload['quantity'],
                    'unit_price' => $payload['unit_price'],
                    'total' => $total,
                ]);

                $payments = $payload['payment_method'] === 'credit' ? [] : [[
                    'amount' => $total,
                    'method' => $payload['payment_method'],
                ]];

                return $this->sales->finalize($sale, $payments);
            });

            Cache::forget($this->cacheKey($token));
            ActivityLogger::record('ai.sale_invoice_created', $sale, newValues: [
                'invoice_number' => $sale->invoice_number,
                'total' => $sale->total,
            ]);

            return [
                'answer' => $locale === 'en'
                    ? "Invoice {$sale->invoice_number} was created successfully and inventory was updated."
                    : "تم إنشاء الفاتورة {$sale->invoice_number} بنجاح، وتحديث المخزون وتسجيل الدفع.",
                'invoice' => [
                    'id' => $sale->id,
                    'number' => $sale->invoice_number,
                    'total' => (float) $sale->total,
                    'status' => $sale->status,
                    'url' => route('admin.module', ['module' => 'sales']),
                ],
            ];
        });
    }

    private function failure(string $locale, string $arabic, string $english): array
    {
        return ['answer' => $locale === 'en' ? $english : $arabic];
    }

    private function cacheKey(string $token): string
    {
        return "ai-action:{$token}";
    }
}
