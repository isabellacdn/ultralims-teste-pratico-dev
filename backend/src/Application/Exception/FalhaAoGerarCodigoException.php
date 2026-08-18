<?php

declare(strict_types=1);

namespace App\Application\Exception;

use RuntimeException;

final class FalhaAoGerarCodigoException extends RuntimeException
{
    public static function aposTentativas(int $tentativas): self
    {
        return new self(sprintf(
            'Nao foi possivel gerar um codigo unico para a amostra apos %d tentativas.',
            $tentativas
        ));
    }
}
