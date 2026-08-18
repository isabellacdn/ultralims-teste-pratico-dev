<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum StatusAmostra: string
{
    case Recebida = 'Recebida';
    case EmAnalise = 'EmAnalise';
    case Concluida = 'Concluida';
    case Rejeitada = 'Rejeitada';

    public function ehFinal(): bool
    {
        return $this === self::Concluida || $this === self::Rejeitada;
    }

    public function transicoesPermitidas(): array
    {
        return match ($this) {
            self::Recebida => [self::EmAnalise, self::Rejeitada],
            self::EmAnalise => [self::Concluida, self::Rejeitada],
            self::Concluida, self::Rejeitada => [],
        };
    }

    public function podeTransicionarPara(self $destino): bool
    {
        return in_array($destino, $this->transicoesPermitidas(), true);
    }
}
