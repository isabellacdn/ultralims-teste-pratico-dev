<?php

declare(strict_types=1);

namespace App\Domain\Enum;

/**
 * Os quatro estados possiveis de uma amostra.
 *
 * Alem de listar os valores, este enum guarda o MAPA DE TRANSICOES: quem pode
 * virar quem. Isso mora aqui, e nao no controller, porque e regra de negocio.
 */
enum StatusAmostra: string
{
    case Recebida = 'Recebida';
    case EmAnalise = 'EmAnalise';
    case Concluida = 'Concluida';
    case Rejeitada = 'Rejeitada';

    /**
     * Regra 5: Concluida e Rejeitada sao estados finais.
     */
    public function ehFinal(): bool
    {
        return $this === self::Concluida || $this === self::Rejeitada;
    }

    /**
     * Para quais estados este status pode caminhar.
     *
     * Regra 3: Concluida so vem de EmAnalise.
     * Regra 4: Rejeitada vem de Recebida ou EmAnalise, nunca de Concluida.
     *
     * @return list<self>
     */
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
