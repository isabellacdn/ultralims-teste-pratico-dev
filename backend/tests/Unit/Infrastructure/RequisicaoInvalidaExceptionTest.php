<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use App\Infrastructure\Http\Middleware\RequisicaoInvalidaException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RequisicaoInvalidaException::class)]
final class RequisicaoInvalidaExceptionTest extends TestCase
{
    #[DataProvider('camposComRotuloProprio')]
    public function testShouldNameFieldsInPlainLanguage(string $campo, string $rotulo): void
    {
        self::assertSame(
            sprintf('O campo %s é obrigatório.', $rotulo),
            RequisicaoInvalidaException::campoObrigatorio($campo)->getMessage(),
        );
    }

    public static function camposComRotuloProprio(): array
    {
        return [
            'data de recebimento' => ['data_recebimento', 'data de recebimento'],
            'data de conclusao' => ['data_conclusao', 'data de conclusão'],
            'responsavel tecnico' => ['responsavel_tecnico', 'responsável técnico'],
            'tipo' => ['tipo', 'tipo'],
        ];
    }

    public function testShouldFallBackToTheFieldNameWhenThereIsNoLabel(): void
    {
        self::assertSame(
            'O campo campo_desconhecido é obrigatório.',
            RequisicaoInvalidaException::campoObrigatorio('campo_desconhecido')->getMessage(),
        );
    }

    public function testShouldListAcceptedValuesWhenTheValueIsInvalid(): void
    {
        self::assertSame(
            'Valor inválido para o campo tipo. Valores aceitos: Agua, Solo.',
            RequisicaoInvalidaException::valorInvalido('tipo', 'Agua, Solo')->getMessage(),
        );
    }

    public function testShouldExplainTheExpectedDateFormat(): void
    {
        self::assertSame(
            'O campo data de recebimento deve estar no formato AAAA-MM-DD.',
            RequisicaoInvalidaException::dataInvalida('data_recebimento')->getMessage(),
        );
    }
}
