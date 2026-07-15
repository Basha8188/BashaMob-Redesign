<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairStatusHistory extends Model
{
    protected $table = 'repair_status_history';

    protected $guarded = [];

    public function ticket()
    {
        return $this->belongsTo(RepairTicket::class, 'repair_ticket_id');
    }
}
