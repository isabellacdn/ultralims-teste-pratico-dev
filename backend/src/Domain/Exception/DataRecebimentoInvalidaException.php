<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class DataRecebimentoInvalidaException extends RegraDeNegocioException
{
    public static function noFuturo(): self
    {
        return new self('A data de recebimento não pode ser posterior a hoje.');
    }
}
