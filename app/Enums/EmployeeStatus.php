<?php

namespace App\Enums;

enum EmployeeStatus: int
{
    case TERMINATED = 0;
    case ON_LEAVE = 2;
    case ACTIVE = 1;

    public static function list(): array
    {
        // Convert to array with explicit numerical indices
        return array_map(function ($value, $key) {
            return ['value' => $key, 'label' => $value];
        }, array_values([
            self::TERMINATED->value => 'Terminated',
            self::ON_LEAVE->value => 'On Leave',
            self::ACTIVE->value => 'Active',
        ]), array_keys([
            self::TERMINATED->value => 'Terminated',
            self::ON_LEAVE->value => 'On Leave',
            self::ACTIVE->value => 'Active',
        ]));
    }

//    public static function list(): array
//    {
//        return [
//            self::TERMINATED->value => 'Terminated',
//            self::ON_LEAVE->value => 'On Leave',
//            self::ACTIVE->value => 'Active',
//        ];
//    }
}

