<?php
namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Delivered = 'delivered';
    case Failed = 'failed';

    public function canTransitTo(self $target): bool
    {
        return match($this) {
            self::Pending => in_array($target, [self::Paid, self::Failed]),
            self::Paid => $target === self::Delivered,
            default => false,
        };
    }
}