<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use App\Application\DTO\CadastrarAmostraInput;
use App\Application\DTO\TransicionarStatusInput;
use App\Application\UseCase\CadastrarAmostra;
use App\Application\UseCase\ConsultarAmostra;
use App\Application\UseCase\ListarAmostras;
use App\Application\UseCase\TransicionarStatusAmostra;
use App\Domain\Enum\StatusAmostra;
use App\Domain\Enum\TipoAmostra;
use App\Infrastructure\Http\Controller\AmostraController;
use App\Infrastructure\Http\Middleware\RequisicaoInvalidaException;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Tests\Support\AmostraRepositoryEmMemoria;

#[CoversClass(AmostraController::class)]
final class AmostraControllerTest extends TestCase
{
    private AmostraRepositoryEmMemoria $repositorio;

    protected function setUp(): void
    {
        $this->repositorio = new AmostraRepositoryEmMemoria();
    }

    public static function datasInexistentes(): array
    {
        return [
            'dia que nao existe no mes' => ['2026-02-31'],
            'mes acima de doze' => ['2026-13-01'],
            'mes e dia zerados' => ['2026-00-00'],
            'ano com dois digitos' => ['26-08-01'],
            'dia acima de trinta e um' => ['2026-08-32'],
        ];
    }

    public static function datasMalFormatadas(): array
    {
        return [
            'sobra de caractere' => ['2026-08-01x'],
            'formato brasileiro' => ['01/08/2026'],
            'texto qualquer' => ['ontem'],
        ];
    }

    #[DataProvider('datasInexistentes')]
    public function testShouldRejectReceivingDateThatDoesNotExist(string $data): void
    {
        $this->expectException(RequisicaoInvalidaException::class);
        $this->expectExceptionMessage('não corresponde a uma data existente');

        $this->controller()->cadastrar(
            $this->requisicaoCom(['tipo' => 'Agua', 'data_recebimento' => $data]),
            new Response(),
        );
    }

    #[DataProvider('datasMalFormatadas')]
    public function testShouldRejectReceivingDateOutOfFormat(string $data): void
    {
        $this->expectException(RequisicaoInvalidaException::class);
        $this->expectExceptionMessage('formato AAAA-MM-DD');

        $this->controller()->cadastrar(
            $this->requisicaoCom(['tipo' => 'Agua', 'data_recebimento' => $data]),
            new Response(),
        );
    }

    public function testShouldNotSaveAnySampleWhenDateDoesNotExist(): void
    {
        try {
            $this->controller()->cadastrar(
                $this->requisicaoCom(['tipo' => 'Agua', 'data_recebimento' => '2026-02-31']),
                new Response(),
            );
        } catch (RequisicaoInvalidaException) {
        }

        self::assertSame([], $this->repositorio->listar());
    }

    public function testShouldKeepRealDateExactlyAsInformed(): void
    {
        $resposta = $this->controller()->cadastrar(
            $this->requisicaoCom(['tipo' => 'Agua', 'data_recebimento' => '2026-02-28']),
            new Response(),
        );

        self::assertSame(201, $resposta->getStatusCode());
        self::assertSame('2026-02-28', $this->corpoDe($resposta)['data_recebimento']);
    }

    public function testShouldKeepLeapDayThatReallyExists(): void
    {
        $resposta = $this->controller()->cadastrar(
            $this->requisicaoCom(['tipo' => 'Agua', 'data_recebimento' => '2024-02-29']),
            new Response(),
        );

        self::assertSame('2024-02-29', $this->corpoDe($resposta)['data_recebimento']);
    }

    public function testShouldRejectConclusionDateThatDoesNotExist(): void
    {
        $id = $this->amostraEmAnalise();

        $this->expectException(RequisicaoInvalidaException::class);
        $this->expectExceptionMessage('não corresponde a uma data existente');

        $this->controller()->transicionarStatus(
            $this->requisicaoCom(['status' => 'Concluida', 'data_conclusao' => '2026-02-31']),
            new Response(),
            ['id' => (string) $id],
        );
    }

    private function amostraEmAnalise(): int
    {
        $amostra = (new CadastrarAmostra($this->repositorio, 'ISABELLA'))->executar(
            new CadastrarAmostraInput(TipoAmostra::Agua, new DateTimeImmutable('2026-02-01'), 'Ana'),
        );

        (new TransicionarStatusAmostra($this->repositorio))->executar(
            new TransicionarStatusInput($amostra->id(), StatusAmostra::EmAnalise),
        );

        return $amostra->id();
    }

    private function controller(): AmostraController
    {
        return new AmostraController(
            new CadastrarAmostra($this->repositorio, 'ISABELLA'),
            new ListarAmostras($this->repositorio),
            new ConsultarAmostra($this->repositorio),
            new TransicionarStatusAmostra($this->repositorio),
        );
    }

    private function requisicaoCom(array $corpo): ServerRequestInterface
    {
        return (new ServerRequestFactory())
            ->createServerRequest('POST', '/amostras')
            ->withParsedBody($corpo);
    }

    private function corpoDe(ResponseInterface $resposta): array
    {
        $resposta->getBody()->rewind();

        return json_decode((string) $resposta->getBody(), true);
    }
}
