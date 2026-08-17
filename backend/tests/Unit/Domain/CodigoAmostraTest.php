<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\CodigoAmostraInvalidoException;
use App\Domain\ValueObject\CodigoAmostra;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Formato do codigo da amostra — secao 2.3 do enunciado:
 * {SEU-PREFIXO}-{ANO}-{SEQUENCIAL}, ex: ISABELLA-2026-0001.
 */
final class CodigoAmostraTest extends TestCase
{
    public function test_gera_codigo_com_sequencial_de_quatro_digitos(): void
    {
        self::assertSame('ISABELLA-2026-0001', CodigoAmostra::gerar('ISABELLA', 2026, 1)->valor());
        self::assertSame('ISABELLA-2026-0042', CodigoAmostra::gerar('ISABELLA', 2026, 42)->valor());
        self::assertSame('ISABELLA-2026-9999', CodigoAmostra::gerar('ISABELLA', 2026, 9999)->valor());
    }

    public function test_o_ano_faz_parte_do_codigo(): void
    {
        self::assertSame('ISABELLA-2027-0001', CodigoAmostra::gerar('ISABELLA', 2027, 1)->valor());
    }

    #[DataProvider('sequenciaisInvalidos')]
    public function test_recusa_sequencial_fora_do_intervalo(int $sequencial): void
    {
        $this->expectException(CodigoAmostraInvalidoException::class);

        CodigoAmostra::gerar('ISABELLA', 2026, $sequencial);
    }

    #[DataProvider('codigosInvalidos')]
    public function test_recusa_codigo_fora_do_formato(string $valor): void
    {
        $this->expectException(CodigoAmostraInvalidoException::class);

        CodigoAmostra::de($valor);
    }

    public function test_aceita_codigo_ja_existente_no_formato_correto(): void
    {
        self::assertSame('ISABELLA-2026-0007', CodigoAmostra::de('ISABELLA-2026-0007')->valor());
    }

    public function test_dois_codigos_com_o_mesmo_texto_sao_iguais(): void
    {
        $a = CodigoAmostra::gerar('ISABELLA', 2026, 1);
        $b = CodigoAmostra::de('ISABELLA-2026-0001');

        self::assertTrue($a->ehIgual($b));
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function sequenciaisInvalidos(): iterable
    {
        yield 'zero' => [0];
        yield 'negativo' => [-1];
        yield 'acima do limite' => [10000];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function codigosInvalidos(): iterable
    {
        yield 'vazio' => [''];
        yield 'sem prefixo' => ['-2026-0001'];
        yield 'sem ano' => ['ISABELLA-0001'];
        yield 'sequencial curto' => ['ISABELLA-2026-1'];
        yield 'ano curto' => ['ISABELLA-26-0001'];
        yield 'separador errado' => ['ISABELLA_2026_0001'];
        yield 'com caractere invalido' => ['ISABELLA!-2026-0001'];
    }
}
