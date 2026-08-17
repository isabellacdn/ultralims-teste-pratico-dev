<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum TipoAmostra: string
{
    case Agua = 'Agua';
    case Solo = 'Solo';
    case Ar = 'Ar';
    case Efluente = 'Efluente';
}
