<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class CodigoAmostraInvalidoException extends RegraDeNegocioException
{
    public static function formato(string $valor): self
    {
        return new self(sprintf(
            'Codigo de amostra invalido: "%s". O formato esperado e {PREFIXO}-{ANO}-{SEQUENCIAL}, por exemplo ISABELLA-2026-0001.',
            $valor
        ));
    }

    public static function sequencialForaDoIntervalo(int $sequencial): self
    {
        return new self(sprintf(
            'Sequencial %d fora do intervalo permitido (1 a 9999).',
            $sequencial
        ));
    }
}
