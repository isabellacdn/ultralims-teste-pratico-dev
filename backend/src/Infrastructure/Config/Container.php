<?php

declare(strict_types=1);

namespace App\Infrastructure\Config;

use App\Application\UseCase\CadastrarAmostra;
use App\Domain\Repository\AmostraRepositoryInterface;
use App\Infrastructure\Persistence\AmostraRepositoryMySQL;
use DI\ContainerBuilder;
use PDO;
use Psr\Container\ContainerInterface;

use function DI\autowire;
use function DI\factory;

final class Container
{
    public static function criar(): ContainerInterface
    {
        $builder = new ContainerBuilder();

        $builder->addDefinitions([
            PDO::class => factory(static fn (): PDO => ConexaoBanco::criar([
                'host' => Ambiente::texto('DB_HOST', '127.0.0.1'),
                'port' => Ambiente::inteiro('DB_PORT', 3306),
                'database' => Ambiente::texto('DB_DATABASE', 'ultralims'),
                'username' => Ambiente::texto('DB_USERNAME', 'ultralims'),
                'password' => Ambiente::texto('DB_PASSWORD', ''),
            ])),

            AmostraRepositoryInterface::class => autowire(AmostraRepositoryMySQL::class),

            CadastrarAmostra::class => factory(static fn (ContainerInterface $c): CadastrarAmostra => new CadastrarAmostra(
                $c->get(AmostraRepositoryInterface::class),
                Ambiente::texto('CODIGO_PREFIXO', 'AMOSTRA'),
            )),
        ]);

        return $builder->build();
    }
}
