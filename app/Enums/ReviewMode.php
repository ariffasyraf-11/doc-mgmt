<?php

namespace App\Enums;

enum ReviewMode: string
{
    case Sequential = 'sequential';
    case Parallel = 'parallel';

    public function label(): string
    {
        return match ($this) {
            self::Sequential => 'Sequential',
            self::Parallel => 'Parallel',
        };
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $mode) {
            $options[$mode->value] = $mode->label();
        }

        return $options;
    }
}
