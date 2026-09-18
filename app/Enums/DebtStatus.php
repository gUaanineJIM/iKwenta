<?php

namespace App\Enums;

enum DebtStatus: string
{
    case Unpaid = 'unpaid';

    case PartiallyPaid = 'partially_paid';

    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::PartiallyPaid => 'Partially Paid',
            self::Paid => 'Paid',
        };
    }

    public function isFullyPaid(): bool
    {
        return $this === self::Paid;
    }
}
