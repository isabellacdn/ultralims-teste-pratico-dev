<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use RuntimeException;

final class RequisicaoInvalidaException extends RuntimeException
{
    private const ROTULOS = [
        'tipo' => 'tipo',
        'status' => 'status',
        'data_recebimento' => 'data de recebimento',
        'data_conclusao' => 'data de conclusão',
        'responsavel_tecnico' => 'responsável técnico',
    ];

    public static function campoObrigatorio(string $campo): self
    {
        return new self(sprintf('O campo %s é obrigatório.', self::rotuloDe($campo)));
    }

    public static function valorInvalido(string $campo, string $permitidos): self
    {
        return new self(sprintf(
            'Valor inválido para o campo %s. Valores aceitos: %s.',
            self::rotuloDe($campo),
            $permitidos,
        ));
    }

    public static function dataInvalida(string $campo): self
    {
        return new self(sprintf(
            'O campo %s deve estar no formato AAAA-MM-DD.',
            self::rotuloDe($campo),
        ));
    }

    private static function rotuloDe(string $campo): string
    {
        return self::ROTULOS[$campo] ?? $campo;
    }
}
