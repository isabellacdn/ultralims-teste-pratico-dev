<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use App\Infrastructure\Http\Middleware\Cors;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

#[CoversClass(Cors::class)]
final class CorsTest extends TestCase
{
    private const ORIGEM_DO_FRONTEND = 'http://localhost:3001';

    public function testShouldAnswerPreflightWithoutReachingTheRoute(): void
    {
        $rota = $this->rotaQueResponde(200);

        $resposta = $this->cors()->process($this->requisicao('OPTIONS', self::ORIGEM_DO_FRONTEND), $rota);

        self::assertSame(204, $resposta->getStatusCode());
        self::assertFalse($rota->foiChamada);
        self::assertSame(self::ORIGEM_DO_FRONTEND, $resposta->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testShouldAllowConfiguredOrigin(): void
    {
        $resposta = $this->cors()->process(
            $this->requisicao('GET', self::ORIGEM_DO_FRONTEND),
            $this->rotaQueResponde(200),
        );

        self::assertSame(self::ORIGEM_DO_FRONTEND, $resposta->getHeaderLine('Access-Control-Allow-Origin'));
        self::assertSame('Origin', $resposta->getHeaderLine('Vary'));
    }

    public function testShouldRefuseOriginOutsideTheList(): void
    {
        $resposta = $this->cors()->process(
            $this->requisicao('GET', 'http://site-nao-autorizado.com'),
            $this->rotaQueResponde(200),
        );

        self::assertFalse($resposta->hasHeader('Access-Control-Allow-Origin'));
    }

    public function testShouldKeepHeadersOnErrorResponses(): void
    {
        $resposta = $this->cors()->process(
            $this->requisicao('PATCH', self::ORIGEM_DO_FRONTEND),
            $this->rotaQueResponde(422),
        );

        self::assertSame(422, $resposta->getStatusCode());
        self::assertSame(self::ORIGEM_DO_FRONTEND, $resposta->getHeaderLine('Access-Control-Allow-Origin'));
    }

    private function cors(): Cors
    {
        return Cors::deLista(self::ORIGEM_DO_FRONTEND . ', http://localhost:3002');
    }

    private function requisicao(string $metodo, string $origem): ServerRequestInterface
    {
        return (new ServerRequestFactory())
            ->createServerRequest($metodo, 'http://localhost:8081/amostras')
            ->withHeader('Origin', $origem);
    }

    private function rotaQueResponde(int $status): RequestHandlerInterface
    {
        return new class ($status) implements RequestHandlerInterface {
            public bool $foiChamada = false;

            public function __construct(private readonly int $status)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->foiChamada = true;

                return (new Response())->withStatus($this->status);
            }
        };
    }
}
