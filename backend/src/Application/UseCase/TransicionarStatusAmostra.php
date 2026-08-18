<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\TransicionarStatusInput;
use App\Application\Exception\AmostraNaoEncontradaException;
use App\Domain\Entity\Amostra;
use App\Domain\Repository\AmostraRepositoryInterface;

final readonly class TransicionarStatusAmostra
{
    public function __construct(private AmostraRepositoryInterface $repositorio)
    {
    }

    public function executar(TransicionarStatusInput $entrada): Amostra
    {
        $amostra = $this->repositorio->buscarPorId($entrada->id)
            ?? throw AmostraNaoEncontradaException::comId($entrada->id);

        if ($entrada->responsavelTecnico !== null) {
            $amostra->definirResponsavelTecnico($entrada->responsavelTecnico);
        }

        $amostra->transicionarPara($entrada->novoStatus, $entrada->dataConclusao);

        $this->repositorio->salvar($amostra);

        return $amostra;
    }
}
