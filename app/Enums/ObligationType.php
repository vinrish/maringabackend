<?php

namespace App\Enums;

enum ObligationType: int
{
    case INDIVIDUAL = 0;
    case COMPANY = 1;

    public function label(): string
    {
        return match ($this) {
            self::INDIVIDUAL => 'Individual',
            self::COMPANY => 'Company',
        };
    }

    public static function fromInt(int $value): self
    {
        return match ($value) {
            0 => self::INDIVIDUAL,
            1 => self::COMPANY,
            default => throw new \InvalidArgumentException('Invalid type value')
        };
    }
}
