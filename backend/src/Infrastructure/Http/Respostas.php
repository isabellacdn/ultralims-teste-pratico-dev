<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Psr\Http\Message\ResponseInterface;

final class Respostas
{
    public static function json(ResponseInterface $resposta, array $dados, int $status = 200): ResponseInterface
    {
        $resposta->getBody()->write(json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $resposta
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    public static function erro(ResponseInterface $resposta, string $mensagem, int $status): ResponseInterface
    {
        return self::json($resposta, ['erro' => $mensagem], $status);
    }
}
