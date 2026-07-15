<?php

namespace App\Models;

use App\Enums\ProductTrackingType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['tracking_type' => ProductTrackingType::class, 'purchase_price' => 'decimal:2', 'sale_price' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function serials()
    {
        return $this->hasMany(ProductSerial::class);
    }

    public function stocks()
    {
        return $this->hasMany(WarehouseStock::class);
    }
}
