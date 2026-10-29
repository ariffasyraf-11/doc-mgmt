<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case DocumentManager = 'document_manager';
    case Reviewer = 'reviewer';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::DocumentManager => 'Document Manager',
            self::Reviewer => 'Reviewer',
            self::Employee => 'Employee',
        };
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $role) {
            $options[$role->value] = $role->label();
        }
        return $options;
    }
}
