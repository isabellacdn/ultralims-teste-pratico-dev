<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\DTO\CadastrarAmostraInput;
use App\Application\Exception\FalhaAoGerarCodigoException;
use App\Domain\Exception\DataRecebimentoInvalidaException;
use App\Application\UseCase\CadastrarAmostra;
use App\Domain\Enum\StatusAmostra;
use App\Domain\Enum\TipoAmostra;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\AmostraRepositoryEmMemoria;

#[CoversClass(CadastrarAmostra::class)]
final class CadastrarAmostraTest extends TestCase
{
    private AmostraRepositoryEmMemoria $repositorio;

    protected function setUp(): void
    {
        $this->repositorio = new AmostraRepositoryEmMemoria();
    }

    public function testShouldCreateSampleWithGeneratedCode(): void
    {
        $amostra = $this->useCase()->executar($this->input());

        self::assertSame('ISABELLA-2026-0001', $amostra->codigo()->valor());
        self::assertSame(StatusAmostra::Recebida, $amostra->status());
        self::assertSame(1, $amostra->id());
    }

    public function testShouldIncrementSequenceForEachSample(): void
    {
        $useCase = $this->useCase();

        $primeira = $useCase->executar($this->input());
        $segunda = $useCase->executar($this->input());
        $terceira = $useCase->executar($this->input());

        self::assertSame('ISABELLA-2026-0001', $primeira->codigo()->valor());
        self::assertSame('ISABELLA-2026-0002', $segunda->codigo()->valor());
        self::assertSame('ISABELLA-2026-0003', $terceira->codigo()->valor());
    }

    public function testShouldUseReceiptYearInGeneratedCode(): void
    {
        $amostra = $this->useCase()->executar(
            new CadastrarAmostraInput(TipoAmostra::Solo, new DateTimeImmutable('2025-01-05'))
        );

        self::assertSame('ISABELLA-2025-0001', $amostra->codigo()->valor());
    }

    public function testShouldRetrySilentlyWhenCodeCollides(): void
    {
        $this->repositorio->forcarColisoes(2);

        $amostra = $this->useCase()->executar($this->input());

        self::assertSame('ISABELLA-2026-0001', $amostra->codigo()->valor());
        self::assertNotNull($amostra->id());
    }

    public function testShouldGiveUpAfterThreeCollisions(): void
    {
        $this->repositorio->forcarColisoes(3);

        $this->expectException(FalhaAoGerarCodigoException::class);

        $this->useCase()->executar($this->input());
    }

    public function testShouldKeepTechnicianProvidedAtCreation(): void
    {
        $amostra = $this->useCase()->executar(
            new CadastrarAmostraInput(TipoAmostra::Ar, new DateTimeImmutable('2026-08-10'), 'Ana Souza')
        );

        self::assertSame('Ana Souza', $amostra->responsavelTecnico());
    }

    private function useCase(): CadastrarAmostra
    {
        return new CadastrarAmostra($this->repositorio, 'ISABELLA');
    }

    public function testShouldRejectSampleReceivedInTheFuture(): void
    {
        $this->expectException(DataRecebimentoInvalidaException::class);

        $this->useCase()->executar(
            new CadastrarAmostraInput(TipoAmostra::Agua, new DateTimeImmutable('+1 day')),
        );
    }

    public function testShouldNotSaveAnythingWhenReceivingDateIsInTheFuture(): void
    {
        try {
            $this->useCase()->executar(
                new CadastrarAmostraInput(TipoAmostra::Agua, new DateTimeImmutable('+1 day')),
            );
        } catch (DataRecebimentoInvalidaException) {
        }

        self::assertSame([], $this->repositorio->listar());
    }

    private function input(): CadastrarAmostraInput
    {
        return new CadastrarAmostraInput(TipoAmostra::Agua, new DateTimeImmutable('2026-08-10'));
    }
}
