<?php

namespace App\Enums;

enum RepairStatus: string
{
    case AwaitingInspection = 'awaiting_inspection';
    case Received = 'received';
    case Diagnosing = 'diagnosing';
    case AwaitingQuotation = 'awaiting_quotation';
    case WaitingCustomerApproval = 'waiting_customer_approval';
    case Approved = 'approved';
    case CustomerRejected = 'customer_rejected';
    case OrderingPart = 'ordering_part';
    case ReadyToRepair = 'ready_to_repair';
    case InProgress = 'in_progress';
    case WaitingParts = 'waiting_parts';
    case Completed = 'completed';
    case ReadyForDelivery = 'ready_for_delivery';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
