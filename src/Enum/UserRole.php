<?php

namespace App\Enum;

enum UserRole: string
{
    case ADMIN = 'admin';
    case EMPLOYEE = 'employee';
    case USER = 'user';
    

    public static function getAllRoles(): array
    {
        return [
            self::ADMIN,
            self::EMPLOYEE,
            self::USER,
        ];
    }
}