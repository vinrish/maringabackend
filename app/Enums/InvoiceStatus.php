<?php

namespace App\Enums;

enum InvoiceStatus: int
{
    case Pending = 0;
    case Partial = 1;
    case Complete = 2;

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Partial => 'Partial',
            self::Complete => 'Complete',
        };
    }
}
