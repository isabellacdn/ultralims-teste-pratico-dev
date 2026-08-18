<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use RuntimeException;

final class RequisicaoInvalidaException extends RuntimeException
{
    public static function campoObrigatorio(string $campo): self
    {
        return new self(sprintf('O campo "%s" e obrigatorio.', $campo));
    }

    public static function valorInvalido(string $campo, string $permitidos): self
    {
        return new self(sprintf('Valor invalido para "%s". Valores aceitos: %s.', $campo, $permitidos));
    }

    public static function dataInvalida(string $campo): self
    {
        return new self(sprintf('O campo "%s" deve estar no formato AAAA-MM-DD.', $campo));
    }
}
