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
        'attachments',
        'status',
        'review_message',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['attachments' => 'array', 'reviewed_at' => 'datetime'];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'phone', 'phone');
    }
}
