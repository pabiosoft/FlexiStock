<?php

namespace App\Enum;

enum UserRole: string
{
    case USER = 'ROLE_USER';
    case EMPLOYEE = 'ROLE_EMPLOYEE';
    case MANAGER = 'ROLE_MANAGER';
    case ADMIN = 'ROLE_ADMIN';

    public static function getAllRoles(): array
    {
        return [
            self::USER,
            self::EMPLOYEE,
            self::MANAGER,
            self::ADMIN,
        ];
    }

    public function toString(): string
    {
        return $this->value;
    }
}
