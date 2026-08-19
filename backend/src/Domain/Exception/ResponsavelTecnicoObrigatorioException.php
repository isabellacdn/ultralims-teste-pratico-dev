<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class ResponsavelTecnicoObrigatorioException extends RegraDeNegocioException
{
    public static function paraIniciarAnalise(): self
    {
        return new self('É necessário informar o responsável técnico para colocar a amostra em análise.');
    }

    public static function nomeVazio(): self
    {
        return new self('O nome do responsável técnico não pode ser vazio.');
    }
}
