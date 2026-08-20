<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Enum\StatusAmostra;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(StatusAmostra::class)]
final class StatusAmostraTest extends TestCase
{
    #[DataProvider('statusesWithLabel')]
    public function testShouldExposeReadableLabel(StatusAmostra $status, string $esperado): void
    {
        self::assertSame($esperado, $status->rotulo());
    }

    #[DataProvider('allStatuses')]
    public function testShouldKeepIdentifierFreeOfAccentsAndSpaces(StatusAmostra $status): void
    {
        self::assertMatchesRegularExpression('/^[A-Za-z]+$/', $status->value);
    }

    public static function statusesWithLabel(): array
    {
        return [
            'recebida' => [StatusAmostra::Recebida, 'Recebida'],
            'em analise' => [StatusAmostra::EmAnalise, 'Em análise'],
            'concluida' => [StatusAmostra::Concluida, 'Concluída'],
            'rejeitada' => [StatusAmostra::Rejeitada, 'Rejeitada'],
        ];
    }

    public static function allStatuses(): array
    {
        return array_map(
            static fn (StatusAmostra $status): array => [$status],
            StatusAmostra::cases(),
        );
    }
}
