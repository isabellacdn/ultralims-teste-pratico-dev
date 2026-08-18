<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Amostra;
use App\Domain\Enum\StatusAmostra;
use App\Domain\Enum\TipoAmostra;

interface AmostraRepositoryInterface
{
    public function salvar(Amostra $amostra): void;

    public function buscarPorId(int $id): ?Amostra;

    public function listar(?StatusAmostra $status = null, ?TipoAmostra $tipo = null): array;

    public function proximoSequencial(string $prefixo, int $ano): int;
}
