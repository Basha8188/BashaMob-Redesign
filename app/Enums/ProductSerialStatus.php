<?php

namespace App\Enums;

enum ProductSerialStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Returned = 'returned';
    case InRepair = 'in_repair';
    case Damaged = 'damaged';
    case Transferred = 'transferred';
}
