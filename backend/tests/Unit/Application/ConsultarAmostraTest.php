<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\DTO\CadastrarAmostraInput;
use App\Application\Exception\AmostraNaoEncontradaException;
use App\Application\UseCase\CadastrarAmostra;
use App\Application\UseCase\ConsultarAmostra;
use App\Domain\Enum\TipoAmostra;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\AmostraRepositoryEmMemoria;

#[CoversClass(ConsultarAmostra::class)]
final class ConsultarAmostraTest extends TestCase
{
    private AmostraRepositoryEmMemoria $repositorio;

    protected function setUp(): void
    {
        $this->repositorio = new AmostraRepositoryEmMemoria();
    }

    public function testShouldReturnStoredSample(): void
    {
        $criada = (new CadastrarAmostra($this->repositorio, 'ISABELLA'))->executar(
            new CadastrarAmostraInput(TipoAmostra::Efluente, new DateTimeImmutable('2026-08-10'))
        );

        $encontrada = (new ConsultarAmostra($this->repositorio))->executar((int) $criada->id());

        self::assertSame($criada->codigo()->valor(), $encontrada->codigo()->valor());
    }

    public function testShouldFailWhenSampleDoesNotExist(): void
    {
        $this->expectException(AmostraNaoEncontradaException::class);

        (new ConsultarAmostra($this->repositorio))->executar(404);
    }
}
