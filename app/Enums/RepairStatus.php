<?php

namespace App\Enums;

enum RepairStatus: string
{
    case Received = 'received';
    case Diagnosing = 'diagnosing';
    case WaitingCustomerApproval = 'waiting_customer_approval';
    case Approved = 'approved';
    case InProgress = 'in_progress';
    case WaitingParts = 'waiting_parts';
    case Completed = 'completed';
    case ReadyForDelivery = 'ready_for_delivery';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
