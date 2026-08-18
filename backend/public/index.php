<?php

declare(strict_types=1);

use App\Infrastructure\Config\Ambiente;
use App\Infrastructure\Config\Container;
use App\Infrastructure\Http\Controller\AmostraController;
use App\Infrastructure\Http\Middleware\TratadorDeErros;
use App\Infrastructure\Http\Respostas;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

Ambiente::carregar(__DIR__ . '/..');

AppFactory::setContainer(Container::criar());
$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->add(new TratadorDeErros(Ambiente::booleano('APP_DEBUG', false)));

$app->get('/health', fn (Request $requisicao, Response $resposta): Response =>
    Respostas::json($resposta, ['status' => 'ok']));

$app->group('/amostras', function ($grupo): void {
    $grupo->post('', [AmostraController::class, 'cadastrar']);
    $grupo->get('', [AmostraController::class, 'listar']);
    $grupo->get('/{id:[0-9]+}', [AmostraController::class, 'consultar']);
    $grupo->patch('/{id:[0-9]+}/status', [AmostraController::class, 'transicionarStatus']);
});

$app->run();
