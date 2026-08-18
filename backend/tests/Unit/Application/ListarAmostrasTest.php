<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\DTO\CadastrarAmostraInput;
use App\Application\DTO\TransicionarStatusInput;
use App\Application\UseCase\CadastrarAmostra;
use App\Application\UseCase\ListarAmostras;
use App\Application\UseCase\TransicionarStatusAmostra;
use App\Domain\Enum\StatusAmostra;
use App\Domain\Enum\TipoAmostra;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\AmostraRepositoryEmMemoria;

#[CoversClass(ListarAmostras::class)]
final class ListarAmostrasTest extends TestCase
{
    private AmostraRepositoryEmMemoria $repositorio;

    protected function setUp(): void
    {
        $this->repositorio = new AmostraRepositoryEmMemoria();

        $this->cadastrar(TipoAmostra::Agua);
        $this->cadastrar(TipoAmostra::Solo);
        $rejeitada = $this->cadastrar(TipoAmostra::Agua);

        (new TransicionarStatusAmostra($this->repositorio))->executar(
            new TransicionarStatusInput($rejeitada, StatusAmostra::Rejeitada)
        );
    }

    public function testShouldReturnEverythingWithoutFilters(): void
    {
        self::assertCount(3, $this->useCase()->executar());
    }

    public function testShouldFilterByStatus(): void
    {
        self::assertCount(1, $this->useCase()->executar(StatusAmostra::Rejeitada));
        self::assertCount(2, $this->useCase()->executar(StatusAmostra::Recebida));
    }

    public function testShouldFilterByType(): void
    {
        self::assertCount(2, $this->useCase()->executar(null, TipoAmostra::Agua));
        self::assertCount(1, $this->useCase()->executar(null, TipoAmostra::Solo));
    }

    public function testShouldCombineBothFilters(): void
    {
        self::assertCount(1, $this->useCase()->executar(StatusAmostra::Recebida, TipoAmostra::Agua));
    }

    public function testShouldReturnEmptyWhenNothingMatches(): void
    {
        self::assertSame([], $this->useCase()->executar(StatusAmostra::Concluida));
    }

    private function useCase(): ListarAmostras
    {
        return new ListarAmostras($this->repositorio);
    }

    private function cadastrar(TipoAmostra $tipo): int
    {
        $amostra = (new CadastrarAmostra($this->repositorio, 'ISABELLA'))->executar(
            new CadastrarAmostraInput($tipo, new DateTimeImmutable('2026-08-10'))
        );

        return (int) $amostra->id();
    }
}
