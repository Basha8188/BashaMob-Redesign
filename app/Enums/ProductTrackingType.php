<?php

namespace App\Enums;

enum ProductTrackingType: string
{
    case Quantity = 'quantity';
    case Serial = 'serial';
    case Imei = 'imei';
}
