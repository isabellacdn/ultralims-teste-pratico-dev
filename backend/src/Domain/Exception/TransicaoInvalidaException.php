<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\Enum\StatusAmostra;

final class TransicaoInvalidaException extends RegraDeNegocioException
{
    public static function de(StatusAmostra $atual, StatusAmostra $destino): self
    {
        return new self(sprintf(
            'Nao e possivel transicionar de %s para %s.',
            $atual->value,
            $destino->value
        ));
    }

    /**
     * Regra 5: estados finais nao podem mais ser alterados.
     */
    public static function amostraJaFinalizada(StatusAmostra $atual): self
    {
        return new self(sprintf(
            'A amostra esta com status %s, que e um estado final, e nao pode mais ser alterada.',
            $atual->value
        ));
    }
}
