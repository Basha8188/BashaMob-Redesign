<?php

namespace App\Services;

use App\Models\CustomerDevice;
use App\Models\ProductSerial;
use App\Models\Sale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DevicePassportService
{
    public function search(?string $term = null): LengthAwarePaginator
    {
        $term = trim((string) $term);

        return CustomerDevice::query()
            ->with(['customer', 'productSerial.product'])
            ->when($term, function ($query) use ($term) {
                $query->where(function ($builder) use ($term) {
                    $builder->where('internal_code', 'like', "%{$term}%")
                        ->orWhere('imei_1', 'like', "%{$term}%")
                        ->orWhere('imei_2', 'like', "%{$term}%")
                        ->orWhere('serial_number', 'like', "%{$term}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%"))
                        ->orWhereHas('repairs', fn ($repair) => $repair->where('ticket_number', 'like', "%{$term}%"))
                        ->orWhereHas('productSerial.saleItem.sale', fn ($sale) => $sale->where('invoice_number', 'like', "%{$term}%"))
                        ->orWhereHas('productSerial.purchaseItem.purchase', fn ($purchase) => $purchase->where('invoice_number', 'like', "%{$term}%"));
                });
            })
            ->latest('first_seen_at')
            ->paginate(20)
            ->withQueryString();
    }

    public function create(array $data, int $userId): CustomerDevice
    {
        return DB::transaction(function () use ($data, $userId) {
            $device = CustomerDevice::create([
                ...$data,
                'internal_code' => $data['internal_code'] ?? $this->nextCode(),
                'first_seen_at' => $data['first_seen_at'] ?? now(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            $device->ownershipHistory()->create([
                'customer_id' => $device->customer_id,
                'started_at' => $device->first_seen_at,
                'acquisition_source' => 'manual',
                'created_by' => $userId,
            ]);
            $this->event($device, 'passport_created', 'تم إنشاء جواز سفر الجهاز', null, $device, ['owner_id' => $device->customer_id]);
            ActivityLogger::record('device_passport.created', $device, newValues: $device->toArray());

            return $device->fresh(['customer', 'ownershipHistory']);
        });
    }

    public function update(CustomerDevice $device, array $data, int $userId): CustomerDevice
    {
        return DB::transaction(function () use ($device, $data, $userId) {
            $device = CustomerDevice::lockForUpdate()->findOrFail($device->id);
            $before = $device->toArray();
            $oldOwner = $device->customer_id;
            $device->fill([...$data, 'updated_by' => $userId])->save();
            if ((int) $oldOwner !== (int) $device->customer_id) {
                $device->ownershipHistory()->whereNull('ended_at')->update(['ended_at' => now()]);
                $device->ownershipHistory()->create(['customer_id' => $device->customer_id, 'started_at' => now(), 'acquisition_source' => 'ownership_transfer', 'created_by' => $userId]);
                $this->event($device, 'owner_changed', 'تم تغيير مالك الجهاز', null, $device, ['from_customer_id' => $oldOwner, 'to_customer_id' => $device->customer_id]);
            }
            $this->event($device, 'passport_updated', 'تم تحديث بيانات جواز الجهاز', null, $device);
            ActivityLogger::record('device_passport.updated', $device, $before, $device->fresh()->toArray());

            return $device->fresh(['customer']);
        });
    }

    public function details(CustomerDevice $device): CustomerDevice
    {
        return $device->load([
            'customer', 'productSerial.product', 'productSerial.saleItem.sale.customer', 'productSerial.purchaseItem.purchase.supplier',
            'ownershipHistory.customer', 'repairs.customer', 'repairs.technician', 'repairs.statusHistory', 'repairs.parts.product', 'repairs.inspection.photos',
            'repairs.quotations.items', 'events.actor',
        ]);
    }

    public function syncSoldSerial(ProductSerial $serial, Sale $sale): ?CustomerDevice
    {
        if (! $sale->customer_id) {
            return null;
        }
        $serial->loadMissing('product.brand');
        $identifiers = array_values(array_filter([$serial->imei_1, $serial->imei_2, $serial->serial_number]));
        $device = CustomerDevice::withTrashed()->where(function ($query) use ($serial, $identifiers) {
            $query->where('product_serial_id', $serial->id);
            if ($identifiers) {
                $query->orWhereIn('imei_1', $identifiers)->orWhereIn('imei_2', $identifiers)->orWhereIn('serial_number', $identifiers);
            }
        })->first();

        if (! $device) {
            $device = $this->create([
                'customer_id' => $sale->customer_id,
                'product_serial_id' => $serial->id,
                'type' => $serial->product->name,
                'brand' => $serial->product->brand?->name,
                'model' => $serial->product->name,
                'color' => $serial->color,
                'storage' => $serial->storage,
                'imei_1' => $serial->imei_1,
                'imei_2' => $serial->imei_2,
                'serial_number' => $serial->serial_number,
                'first_seen_at' => $serial->created_at,
            ], (int) ($sale->cashier_id ?: auth()->id()));
        } else {
            if ($device->trashed()) {
                $device->restore();
            }
            $updates = ['product_serial_id' => $serial->id];
            if ((int) $device->customer_id !== (int) $sale->customer_id) {
                $updates['customer_id'] = $sale->customer_id;
            }
            $device = $this->update($device, $updates, (int) ($sale->cashier_id ?: auth()->id()));
        }
        $this->event($device, 'device_sold', 'تم بيع الجهاز', $sale->invoice_number, $sale, ['invoice_number' => $sale->invoice_number, 'total' => (float) $sale->total]);

        return $device;
    }

    public function event(CustomerDevice $device, string $type, string $title, ?string $description = null, ?Model $eventable = null, array $metadata = []): void
    {
        $device->events()->create([
            'event_type' => $type,
            'eventable_type' => $eventable?->getMorphClass(),
            'eventable_id' => $eventable?->getKey(),
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata ?: null,
            'occurred_at' => now(),
            'actor_id' => auth()->id(),
        ]);
    }

    private function nextCode(): string
    {
        do {
            $code = 'DEV-'.now()->format('Y').'-'.strtoupper(bin2hex(random_bytes(4)));
        } while (CustomerDevice::withTrashed()->where('internal_code', $code)->exists());

        return $code;
    }
}
