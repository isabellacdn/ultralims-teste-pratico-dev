<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Entity\Amostra;
use App\Domain\Enum\StatusAmostra;
use App\Domain\Enum\TipoAmostra;
use App\Domain\Exception\DataConclusaoInvalidaException;
use App\Domain\Exception\RegraDeNegocioException;
use App\Domain\Exception\ResponsavelTecnicoObrigatorioException;
use App\Domain\Exception\TransicaoInvalidaException;
use App\Domain\ValueObject\CodigoAmostra;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Amostra::class)]
final class AmostraTest extends TestCase
{
    private const RECEBIMENTO = '2026-08-10';

    public function testShouldCreateSampleWithReceivedStatus(): void
    {
        $amostra = $this->newSample();

        self::assertSame(StatusAmostra::Recebida, $amostra->status());
        self::assertNull($amostra->dataConclusao());
        self::assertNull($amostra->id());
    }

    public function testShouldAllowCreationWithoutTechnician(): void
    {
        self::assertNull($this->newSample()->responsavelTecnico());
    }

    public function testShouldKeepTechnicianProvidedAtCreation(): void
    {
        self::assertSame('Ana Souza', $this->newSample('Ana Souza')->responsavelTecnico());
    }

    public function testShouldRejectAnalysisStartWithoutTechnician(): void
    {
        $amostra = $this->newSample();

        $this->expectException(ResponsavelTecnicoObrigatorioException::class);

        $amostra->transicionarPara(StatusAmostra::EmAnalise);
    }

    public function testShouldKeepStatusUnchangedWhenTransitionIsRejected(): void
    {
        $amostra = $this->newSample();

        $this->attemptRejectedTransition($amostra, StatusAmostra::EmAnalise);

        self::assertSame(StatusAmostra::Recebida, $amostra->status());
    }

    public function testShouldStartAnalysisWhenTechnicianProvidedAtCreation(): void
    {
        $amostra = $this->newSample('Ana Souza');

        $amostra->transicionarPara(StatusAmostra::EmAnalise);

        self::assertSame(StatusAmostra::EmAnalise, $amostra->status());
    }

    public function testShouldStartAnalysisAfterAssigningTechnician(): void
    {
        $amostra = $this->newSample();

        $amostra->definirResponsavelTecnico('Bruno Lima');
        $amostra->transicionarPara(StatusAmostra::EmAnalise);

        self::assertSame(StatusAmostra::EmAnalise, $amostra->status());
        self::assertSame('Bruno Lima', $amostra->responsavelTecnico());
    }

    public function testShouldRejectBlankTechnicianName(): void
    {
        $amostra = $this->newSample();

        $this->expectException(ResponsavelTecnicoObrigatorioException::class);

        $amostra->definirResponsavelTecnico('   ');
    }

    public function testShouldRejectCompletionDirectlyFromReceived(): void
    {
        $amostra = $this->newSample('Ana Souza');

        $this->expectException(TransicaoInvalidaException::class);

        $amostra->transicionarPara(StatusAmostra::Concluida, new DateTimeImmutable('2026-08-15'));
    }

    public function testShouldRejectCompletionWithoutCompletionDate(): void
    {
        $amostra = $this->sampleUnderAnalysis();

        $this->expectException(DataConclusaoInvalidaException::class);

        $amostra->transicionarPara(StatusAmostra::Concluida);
    }

    public function testShouldRejectCompletionDateBeforeReceiptDate(): void
    {
        $amostra = $this->sampleUnderAnalysis();

        $this->expectException(DataConclusaoInvalidaException::class);

        $amostra->transicionarPara(StatusAmostra::Concluida, new DateTimeImmutable('2026-08-09'));
    }

    public function testShouldAcceptCompletionDateEqualToReceiptDate(): void
    {
        $amostra = $this->sampleUnderAnalysis();

        $amostra->transicionarPara(StatusAmostra::Concluida, new DateTimeImmutable(self::RECEBIMENTO));

        self::assertSame(StatusAmostra::Concluida, $amostra->status());
        self::assertSame(self::RECEBIMENTO, $amostra->dataConclusao()?->format('Y-m-d'));
    }

    public function testShouldAcceptCompletionDateAfterReceiptDate(): void
    {
        $amostra = $this->sampleUnderAnalysis();

        $amostra->transicionarPara(StatusAmostra::Concluida, new DateTimeImmutable('2026-08-15'));

        self::assertSame(StatusAmostra::Concluida, $amostra->status());
        self::assertSame('2026-08-15', $amostra->dataConclusao()?->format('Y-m-d'));
    }

    public function testShouldRejectSampleFromReceived(): void
    {
        $amostra = $this->newSample();

        $amostra->transicionarPara(StatusAmostra::Rejeitada);

        self::assertSame(StatusAmostra::Rejeitada, $amostra->status());
    }

    public function testShouldRejectSampleFromUnderAnalysis(): void
    {
        $amostra = $this->sampleUnderAnalysis();

        $amostra->transicionarPara(StatusAmostra::Rejeitada);

        self::assertSame(StatusAmostra::Rejeitada, $amostra->status());
    }

    public function testShouldNotRejectSampleAlreadyCompleted(): void
    {
        $amostra = $this->completedSample();

        $this->expectException(TransicaoInvalidaException::class);

        $amostra->transicionarPara(StatusAmostra::Rejeitada);
    }

    #[DataProvider('allStatuses')]
    public function testShouldBlockAnyTransitionFromCompletedSample(StatusAmostra $destino): void
    {
        $amostra = $this->completedSample();

        $this->expectException(TransicaoInvalidaException::class);

        $amostra->transicionarPara($destino, new DateTimeImmutable('2026-08-20'));
    }

    #[DataProvider('allStatuses')]
    public function testShouldBlockAnyTransitionFromRejectedSample(StatusAmostra $destino): void
    {
        $amostra = $this->rejectedSample();

        $this->expectException(TransicaoInvalidaException::class);

        $amostra->transicionarPara($destino, new DateTimeImmutable('2026-08-20'));
    }

    public function testShouldBlockTechnicianChangeOnFinalizedSample(): void
    {
        $amostra = $this->completedSample();

        $this->expectException(TransicaoInvalidaException::class);

        $amostra->definirResponsavelTecnico('Carla Dias');
    }

    public static function allStatuses(): iterable
    {
        foreach (StatusAmostra::cases() as $status) {
            yield $status->value => [$status];
        }
    }

    private function attemptRejectedTransition(Amostra $amostra, StatusAmostra $destino): void
    {
        try {
            $amostra->transicionarPara($destino);
            self::fail('A transicao deveria ter sido recusada.');
        } catch (RegraDeNegocioException) {
        }
    }

    private function newSample(?string $responsavelTecnico = null): Amostra
    {
        return Amostra::criar(
            CodigoAmostra::gerar('ISABELLA', 2026, 1),
            TipoAmostra::Agua,
            new DateTimeImmutable(self::RECEBIMENTO),
            $responsavelTecnico,
        );
    }

    private function sampleUnderAnalysis(): Amostra
    {
        $amostra = $this->newSample('Ana Souza');
        $amostra->transicionarPara(StatusAmostra::EmAnalise);

        return $amostra;
    }

    private function completedSample(): Amostra
    {
        $amostra = $this->sampleUnderAnalysis();
        $amostra->transicionarPara(StatusAmostra::Concluida, new DateTimeImmutable('2026-08-15'));

        return $amostra;
    }

    private function rejectedSample(): Amostra
    {
        $amostra = $this->newSample();
        $amostra->transicionarPara(StatusAmostra::Rejeitada);

        return $amostra;
    }
}
