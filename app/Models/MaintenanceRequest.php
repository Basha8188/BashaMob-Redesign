<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'name',
        'email',
        'phone',
        'device',
        'brand',
        'problem',
        'status',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'phone', 'phone');
    }
}
