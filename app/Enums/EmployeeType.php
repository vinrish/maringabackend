<?php

namespace App\Enums;

enum EmployeeType: int
{
    case INTERNSHIP = 0;
    case CASUAL = 1;
    case CONTRACT = 2;
    case PART_TIME = 3;
    case FULL_TIME = 4;

    public static function list(): array
    {
        return array_map(function ($value, $key) {
            return ['value' => $key, 'label' => $value];
        }, array_values([
            self::INTERNSHIP->value => 'Internship',
            self::CASUAL->value => 'Casual',
            self::CONTRACT->value => 'Contract',
            self::PART_TIME->value => 'Part-time',
            self::FULL_TIME->value => 'Full-time',
        ]), array_keys([
            self::INTERNSHIP->value => 'Internship',
            self::CASUAL->value => 'Casual',
            self::CONTRACT->value => 'Contract',
            self::PART_TIME->value => 'Part-time',
            self::FULL_TIME->value => 'Full-time',
        ]));
    }

//    public static function list(): array
//    {
//        return [
//            self::INTERNSHIP->value => 'Internship',
//            self::CASUAL->value => 'Casual',
//            self::CONTRACT->value => 'Contract',
//            self::PART_TIME->value => 'Part-time',
//            self::FULL_TIME->value => 'Full-time',
//        ];
//    }
}
