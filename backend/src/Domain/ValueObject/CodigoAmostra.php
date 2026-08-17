<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\CodigoAmostraInvalidoException;
use Stringable;

/**
 * O codigo da amostra no formato {PREFIXO}-{ANO}-{SEQUENCIAL}, ex: ISABELLA-2026-0001.
 *
 * E um Value Object: nao tem identidade propria (dois codigos com o mesmo texto
 * sao a mesma coisa) e e IMUTAVEL. Uma vez criado, so existe valido — nao ha
 * como montar um CodigoAmostra fora do formato.
 */
final class CodigoAmostra implements Stringable
{
    private const PADRAO = '/^[A-Z0-9]+-\d{4}-\d{4}$/';

    private const TAMANHO_SEQUENCIAL = 4;

    private function __construct(private readonly string $valor)
    {
    }

    /**
     * Reconstroi um codigo que ja existe (por exemplo, lido do banco).
     */
    public static function de(string $valor): self
    {
        $valor = strtoupper(trim($valor));

        if (preg_match(self::PADRAO, $valor) !== 1) {
            throw CodigoAmostraInvalidoException::formato($valor);
        }

        return new self($valor);
    }

    /**
     * Monta um codigo novo a partir das partes. O sequencial ganha zeros a
     * esquerda ate 4 digitos: 1 vira "0001".
     */
    public static function gerar(string $prefixo, int $ano, int $sequencial): self
    {
        if ($sequencial < 1 || $sequencial > 9999) {
            throw CodigoAmostraInvalidoException::sequencialForaDoIntervalo($sequencial);
        }

        return self::de(sprintf(
            '%s-%04d-%s',
            strtoupper(trim($prefixo)),
            $ano,
            str_pad((string) $sequencial, self::TAMANHO_SEQUENCIAL, '0', STR_PAD_LEFT)
        ));
    }

    public function valor(): string
    {
        return $this->valor;
    }

    public function ehIgual(self $outro): bool
    {
        return $this->valor === $outro->valor;
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
