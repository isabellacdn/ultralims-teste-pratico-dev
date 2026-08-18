<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Entity\Amostra;

final readonly class AmostraOutput
{
    private function __construct(private array $dados)
    {
    }

    public static function deEntidade(Amostra $amostra): self
    {
        return new self([
            'id' => $amostra->id(),
            'codigo' => $amostra->codigo()->valor(),
            'tipo' => $amostra->tipo()->value,
            'status' => $amostra->status()->value,
            'responsavel_tecnico' => $amostra->responsavelTecnico(),
            'data_recebimento' => $amostra->dataRecebimento()->format('Y-m-d'),
            'data_conclusao' => $amostra->dataConclusao()?->format('Y-m-d'),
        ]);
    }

    public function paraArray(): array
    {
        return $this->dados;
    }
}
