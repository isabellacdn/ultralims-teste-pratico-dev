<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class DataConclusaoInvalidaException extends RegraDeNegocioException
{
    public static function obrigatoria(): self
    {
        return new self('É necessário informar a data de conclusão para concluir a amostra.');
    }

    public static function anteriorAoRecebimento(): self
    {
        return new self('A data de conclusão não pode ser anterior à data de recebimento.');
    }
}
