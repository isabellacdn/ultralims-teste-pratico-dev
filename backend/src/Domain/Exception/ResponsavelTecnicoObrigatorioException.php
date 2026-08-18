<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class ResponsavelTecnicoObrigatorioException extends RegraDeNegocioException
{
    public static function paraIniciarAnalise(): self
    {
        return new self('E necessario informar o responsavel tecnico para colocar a amostra em analise.');
    }

    public static function nomeVazio(): self
    {
        return new self('O nome do responsavel tecnico nao pode ser vazio.');
    }
}
