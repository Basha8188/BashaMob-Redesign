<?php

namespace App\Services;

use App\Models\WarehouseStock;
use Brick\Math\BigDecimal;

class InventoryValuationService
{
    public function summary(): array
    {
        $value = BigDecimal::zero();
        $unknown = 0;
        foreach (WarehouseStock::query()->cursor() as $stock) {
            if ($stock->inventory_value === null && BigDecimal::of($stock->quantity)->isPositive()) {
                $unknown++;
            } else {
                $value = $value->plus($stock->inventory_value ?? '0');
            }
        }

        return ['known_value' => (string) $value->toScale(6), 'unknown_stock_rows' => $unknown, 'complete' => $unknown === 0];
    }

    public function total(): ?string
    {
        $summary = $this->summary();

        return $summary['complete'] ? $summary['known_value'] : null;
    }
}
