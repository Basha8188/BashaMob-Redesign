<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSerialIdentifier extends Model
{
    protected $guarded = [];

    public function productSerial()
    {
        return $this->belongsTo(ProductSerial::class);
    }
}
