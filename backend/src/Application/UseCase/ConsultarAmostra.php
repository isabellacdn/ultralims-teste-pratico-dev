<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Exception\AmostraNaoEncontradaException;
use App\Domain\Entity\Amostra;
use App\Domain\Repository\AmostraRepositoryInterface;

final readonly class ConsultarAmostra
{
    public function __construct(private AmostraRepositoryInterface $repositorio)
    {
    }

    public function executar(int $id): Amostra
    {
        return $this->repositorio->buscarPorId($id)
            ?? throw AmostraNaoEncontradaException::comId($id);
    }
}
