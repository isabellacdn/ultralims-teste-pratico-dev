<?php

declare(strict_types=1);

namespace App\Application\Exception;

use RuntimeException;

final class FalhaAoGerarCodigoException extends RuntimeException
{
    public static function aposTentativas(int $tentativas): self
    {
        return new self(sprintf(
            'Não foi possível gerar um código único para a amostra após %d tentativas.',
            $tentativas
        ));
    }
}
