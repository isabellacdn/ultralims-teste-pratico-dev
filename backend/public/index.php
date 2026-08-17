<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// Le o corpo JSON das requisicoes e entrega ja como array em $request->getParsedBody()
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
// (exibirErros, logarErros, logarDetalhes) — o primeiro true e so para desenvolvimento
$app->addErrorMiddleware(true, true, true);

$app->get('/health', function (Request $request, Response $response): Response {
    $response->getBody()->write(json_encode(['status' => 'ok']));

    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
