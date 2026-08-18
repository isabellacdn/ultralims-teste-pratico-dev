<?php

declare(strict_types=1);

namespace App\Infrastructure\Config;

use Dotenv\Dotenv;

final class Ambiente
{
    public static function carregar(string $caminho): void
    {
        Dotenv::createImmutable($caminho)->safeLoad();
    }

    public static function texto(string $chave, string $padrao = ''): string
    {
        $valor = $_ENV[$chave] ?? getenv($chave);

        return is_string($valor) && $valor !== '' ? $valor : $padrao;
    }

    public static function inteiro(string $chave, int $padrao): int
    {
        $valor = self::texto($chave);

        return $valor === '' ? $padrao : (int) $valor;
    }

    public static function booleano(string $chave, bool $padrao): bool
    {
        $valor = strtolower(self::texto($chave));

        return $valor === '' ? $padrao : in_array($valor, ['1', 'true', 'on', 'yes'], true);
    }
}
