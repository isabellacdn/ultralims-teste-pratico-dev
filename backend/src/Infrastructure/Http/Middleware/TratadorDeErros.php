<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Application\Exception\AmostraNaoEncontradaException;
use App\Domain\Exception\RegraDeNegocioException;
use App\Infrastructure\Http\Respostas;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Response;
use Throwable;

final readonly class TratadorDeErros implements MiddlewareInterface
{
    public function __construct(private bool $exibirDetalhes = false)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (AmostraNaoEncontradaException $erro) {
            return Respostas::erro(new Response(), $erro->getMessage(), 404);
        } catch (HttpNotFoundException) {
            return Respostas::erro(new Response(), 'Rota não encontrada.', 404);
        } catch (HttpMethodNotAllowedException $erro) {
            return Respostas::erro(
                new Response(),
                sprintf('Método não permitido. Use: %s.', implode(', ', $erro->getAllowedMethods())),
                405,
            );
        } catch (RegraDeNegocioException $erro) {
            return Respostas::erro(new Response(), $erro->getMessage(), 422);
        } catch (RequisicaoInvalidaException $erro) {
            return Respostas::erro(new Response(), $erro->getMessage(), 400);
        } catch (Throwable $erro) {
            return Respostas::erro(
                new Response(),
                $this->exibirDetalhes ? $erro->getMessage() : 'Erro interno no servidor.',
                500,
            );
        }
    }
}
