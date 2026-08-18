<?php

declare(strict_types=1);

namespace App\Application\Exception;

use RuntimeException;

final class AmostraNaoEncontradaException extends RuntimeException
{
    public static function comId(int $id): self
    {
        return new self(sprintf('Amostra %d nao encontrada.', $id));
    }
}
