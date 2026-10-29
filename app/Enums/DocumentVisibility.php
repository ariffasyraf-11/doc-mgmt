<?php

namespace App\Enums;

enum DocumentVisibility: string
{
    case Organization = 'organization';
    case Department = 'department';
    case Restricted = 'restricted';
    case Confidential = 'confidential';

    public function label(): string
    {
        return match ($this) {
            self::Organization => 'Organization-wide',
            self::Department => 'Department-only',
            self::Restricted => 'Restricted',
            self::Confidential => 'Confidential',
        };
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $visibility) {
            $options[$visibility->value] = $visibility->label();
        }
        return $options;
    }
}
