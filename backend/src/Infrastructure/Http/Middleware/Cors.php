<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class Cors implements MiddlewareInterface
{
    private const METODOS_PERMITIDOS = 'GET, POST, PATCH, OPTIONS';
    private const CABECALHOS_PERMITIDOS = 'Content-Type';
    private const SEGUNDOS_DE_CACHE_DO_PREFLIGHT = '600';

    public function __construct(private array $origensPermitidas)
    {
    }

    public static function deLista(string $origens): self
    {
        $separadas = array_map(trim(...), explode(',', $origens));

        return new self(array_values(array_filter($separadas, static fn (string $origem): bool => $origem !== '')));
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $resposta = $this->ehPreflight($request)
            ? (new Response())->withStatus(204)
            : $handler->handle($request);

        $origem = $request->getHeaderLine('Origin');

        return $this->ehOrigemPermitida($origem)
            ? $this->comCabecalhosDeCors($resposta, $origem)
            : $resposta;
    }

    private function ehPreflight(ServerRequestInterface $request): bool
    {
        return strtoupper($request->getMethod()) === 'OPTIONS';
    }

    private function ehOrigemPermitida(string $origem): bool
    {
        return $origem !== '' && in_array($origem, $this->origensPermitidas, true);
    }

    private function comCabecalhosDeCors(ResponseInterface $resposta, string $origem): ResponseInterface
    {
        return $resposta
            ->withHeader('Access-Control-Allow-Origin', $origem)
            ->withHeader('Access-Control-Allow-Methods', self::METODOS_PERMITIDOS)
            ->withHeader('Access-Control-Allow-Headers', self::CABECALHOS_PERMITIDOS)
            ->withHeader('Access-Control-Max-Age', self::SEGUNDOS_DE_CACHE_DO_PREFLIGHT)
            ->withHeader('Vary', 'Origin');
    }
}
