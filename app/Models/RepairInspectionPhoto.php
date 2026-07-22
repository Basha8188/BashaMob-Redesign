<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairInspectionPhoto extends Model
{
    protected $guarded = [];

    public function inspection()
    {
        return $this->belongsTo(RepairInspection::class, 'repair_inspection_id');
    }
}
