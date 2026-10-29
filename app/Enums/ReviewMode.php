<?php

namespace App\Enums;

enum ReviewMode: string
{
    case Sequential = 'sequential';
    case Parallel = 'parallel';
}
