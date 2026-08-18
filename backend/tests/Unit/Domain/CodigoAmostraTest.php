<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\CodigoAmostraInvalidoException;
use App\Domain\ValueObject\CodigoAmostra;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CodigoAmostra::class)]
final class CodigoAmostraTest extends TestCase
{
    #[DataProvider('sequencesWithExpectedCode')]
    public function testShouldGenerateCodeWithFourDigitSequence(int $sequencial, string $esperado): void
    {
        self::assertSame($esperado, CodigoAmostra::gerar('ISABELLA', 2026, $sequencial)->valor());
    }

    public function testShouldIncludeYearInCode(): void
    {
        self::assertSame('ISABELLA-2027-0001', CodigoAmostra::gerar('ISABELLA', 2027, 1)->valor());
    }

    #[DataProvider('invalidSequences')]
    public function testShouldRejectSequenceOutOfRange(int $sequencial): void
    {
        $this->expectException(CodigoAmostraInvalidoException::class);

        CodigoAmostra::gerar('ISABELLA', 2026, $sequencial);
    }

    #[DataProvider('invalidCodes')]
    public function testShouldRejectCodeWithInvalidFormat(string $valor): void
    {
        $this->expectException(CodigoAmostraInvalidoException::class);

        CodigoAmostra::de($valor);
    }

    public function testShouldAcceptExistingCodeInValidFormat(): void
    {
        self::assertSame('ISABELLA-2026-0007', CodigoAmostra::de('ISABELLA-2026-0007')->valor());
    }

    public function testShouldConsiderCodesWithSameValueEqual(): void
    {
        $a = CodigoAmostra::gerar('ISABELLA', 2026, 1);
        $b = CodigoAmostra::de('ISABELLA-2026-0001');

        self::assertTrue($a->ehIgual($b));
    }

    public static function sequencesWithExpectedCode(): iterable
    {
        yield 'first of the year' => [1, 'ISABELLA-2026-0001'];
        yield 'two digits' => [42, 'ISABELLA-2026-0042'];
        yield 'upper bound' => [9999, 'ISABELLA-2026-9999'];
    }

    public static function invalidSequences(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
        yield 'above upper bound' => [10000];
    }

    public static function invalidCodes(): iterable
    {
        yield 'empty' => [''];
        yield 'missing prefix' => ['-2026-0001'];
        yield 'missing year' => ['ISABELLA-0001'];
        yield 'sequence too short' => ['ISABELLA-2026-1'];
        yield 'year too short' => ['ISABELLA-26-0001'];
        yield 'wrong separator' => ['ISABELLA_2026_0001'];
        yield 'invalid character' => ['ISABELLA!-2026-0001'];
    }
}
