<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\DTO\CadastrarAmostraInput;
use App\Application\DTO\TransicionarStatusInput;
use App\Application\Exception\AmostraNaoEncontradaException;
use App\Application\UseCase\CadastrarAmostra;
use App\Application\UseCase\TransicionarStatusAmostra;
use App\Domain\Enum\StatusAmostra;
use App\Domain\Enum\TipoAmostra;
use App\Domain\Exception\ResponsavelTecnicoObrigatorioException;
use App\Domain\Exception\TransicaoInvalidaException;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\AmostraRepositoryEmMemoria;

#[CoversClass(TransicionarStatusAmostra::class)]
final class TransicionarStatusAmostraTest extends TestCase
{
    private AmostraRepositoryEmMemoria $repositorio;

    protected function setUp(): void
    {
        $this->repositorio = new AmostraRepositoryEmMemoria();
    }

    public function testShouldFailWhenSampleDoesNotExist(): void
    {
        $this->expectException(AmostraNaoEncontradaException::class);

        $this->useCase()->executar(
            new TransicionarStatusInput(999, StatusAmostra::Rejeitada)
        );
    }

    public function testShouldAssignTechnicianBeforeTransitioning(): void
    {
        $id = $this->novaAmostra();

        $amostra = $this->useCase()->executar(
            new TransicionarStatusInput($id, StatusAmostra::EmAnalise, null, 'Ana Souza')
        );

        self::assertSame(StatusAmostra::EmAnalise, $amostra->status());
        self::assertSame('Ana Souza', $amostra->responsavelTecnico());
    }

    public function testShouldPersistTheNewStatus(): void
    {
        $id = $this->novaAmostra();

        $this->useCase()->executar(
            new TransicionarStatusInput($id, StatusAmostra::EmAnalise, null, 'Ana Souza')
        );

        self::assertSame(
            StatusAmostra::EmAnalise,
            $this->repositorio->buscarPorId($id)?->status()
        );
    }

    public function testShouldPropagateBusinessRuleViolation(): void
    {
        $id = $this->novaAmostra();

        $this->expectException(ResponsavelTecnicoObrigatorioException::class);

        $this->useCase()->executar(
            new TransicionarStatusInput($id, StatusAmostra::EmAnalise)
        );
    }

    public function testShouldNotPersistWhenTransitionIsRejected(): void
    {
        $id = $this->novaAmostra();

        try {
            $this->useCase()->executar(new TransicionarStatusInput($id, StatusAmostra::EmAnalise));
        } catch (ResponsavelTecnicoObrigatorioException) {
        }

        self::assertSame(
            StatusAmostra::Recebida,
            $this->repositorio->buscarPorId($id)?->status()
        );
    }

    public function testShouldCompleteSampleUnderAnalysis(): void
    {
        $id = $this->novaAmostra();
        $useCase = $this->useCase();

        $useCase->executar(new TransicionarStatusInput($id, StatusAmostra::EmAnalise, null, 'Ana Souza'));

        $amostra = $useCase->executar(new TransicionarStatusInput(
            $id,
            StatusAmostra::Concluida,
            new DateTimeImmutable('2026-08-15'),
        ));

        self::assertSame(StatusAmostra::Concluida, $amostra->status());
        self::assertSame('2026-08-15', $amostra->dataConclusao()?->format('Y-m-d'));
    }

    public function testShouldBlockTransitionFromFinalStatus(): void
    {
        $id = $this->novaAmostra();
        $useCase = $this->useCase();

        $useCase->executar(new TransicionarStatusInput($id, StatusAmostra::Rejeitada));

        $this->expectException(TransicaoInvalidaException::class);

        $useCase->executar(new TransicionarStatusInput($id, StatusAmostra::EmAnalise, null, 'Ana Souza'));
    }

    private function useCase(): TransicionarStatusAmostra
    {
        return new TransicionarStatusAmostra($this->repositorio);
    }

    private function novaAmostra(): int
    {
        $amostra = (new CadastrarAmostra($this->repositorio, 'ISABELLA'))->executar(
            new CadastrarAmostraInput(TipoAmostra::Agua, new DateTimeImmutable('2026-08-10'))
        );

        return (int) $amostra->id();
    }
}
