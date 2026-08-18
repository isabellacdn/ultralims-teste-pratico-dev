<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Enum\StatusAmostra;
use App\Domain\Enum\TipoAmostra;
use App\Domain\Repository\AmostraRepositoryInterface;

final readonly class ListarAmostras
{
    public function __construct(private AmostraRepositoryInterface $repositorio)
    {
    }

    public function executar(?StatusAmostra $status = null, ?TipoAmostra $tipo = null): array
    {
        return $this->repositorio->listar($status, $tipo);
    }
}
