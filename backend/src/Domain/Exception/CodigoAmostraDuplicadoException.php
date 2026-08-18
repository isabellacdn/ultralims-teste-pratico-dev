<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use RuntimeException;

final class CodigoAmostraDuplicadoException extends RuntimeException
{
    public static function de(string $codigo): self
    {
        return new self(sprintf('Ja existe uma amostra com o codigo %s.', $codigo));
    }
}
