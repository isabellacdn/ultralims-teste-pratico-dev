<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class DataConclusaoInvalidaException extends RegraDeNegocioException
{
    public static function obrigatoria(): self
    {
        return new self('E necessario informar a data de conclusao para concluir a amostra.');
    }

    public static function anteriorAoRecebimento(): self
    {
        return new self('A data de conclusao nao pode ser anterior a data de recebimento.');
    }
}
