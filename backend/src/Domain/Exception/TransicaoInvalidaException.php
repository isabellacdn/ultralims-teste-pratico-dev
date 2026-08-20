<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\Enum\StatusAmostra;

final class TransicaoInvalidaException extends RegraDeNegocioException
{
    public static function de(StatusAmostra $atual, StatusAmostra $destino): self
    {
        return new self(sprintf(
            'Não é possível transicionar de %s para %s.',
            $atual->rotulo(),
            $destino->rotulo()
        ));
    }

    public static function amostraJaFinalizada(StatusAmostra $atual): self
    {
        return new self(sprintf(
            'A amostra está com status %s, que é um estado final, e não pode mais ser alterada.',
            $atual->rotulo()
        ));
    }
}
