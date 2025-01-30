<?php

namespace App\Enums;

enum ObligationFrequency: int
{
    case DAILY = 0;
    case WEEKLY = 1;
    case MONTHLY = 2;
    case QUARTERLY = 3;
    case SEMI_ANNUALLY = 4;
    case YEARLY = 5;

    public function label(): string
    {
        return match ($this) {
            self::DAILY => 'Daily',
            self::WEEKLY => 'Weekly',
            self::MONTHLY => 'Monthly',
            self::QUARTERLY => 'Quarterly',
            self::SEMI_ANNUALLY => 'Semi-Annually',
            self::YEARLY => 'Yearly',
        };
    }

    public static function fromInt(int $value): self
    {
        return match ($value) {
            0 => self::DAILY,
            1 => self::WEEKLY,
            2 => self::MONTHLY,
            3 => self::QUARTERLY,
            4 => self::SEMI_ANNUALLY,
            5 => self::YEARLY,
            default => throw new \InvalidArgumentException('Invalid frequency value')
        };
    }
}
