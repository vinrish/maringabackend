<?php

namespace App\Enums;

enum UserRole: int
{
    case ADMIN = 1;
    case CLIENT = 2;
    case EMPLOYEE = 3;
}
