<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Entity\Amostra;
use App\Domain\Enum\StatusAmostra;
use App\Domain\Enum\TipoAmostra;
use App\Domain\Exception\CodigoAmostraDuplicadoException;
use App\Domain\Repository\AmostraRepositoryInterface;

final class AmostraRepositoryEmMemoria implements AmostraRepositoryInterface
{
    private array $amostras = [];

    private int $proximoId = 1;

    private int $colisoesRestantes = 0;

    public function forcarColisoes(int $quantidade): void
    {
        $this->colisoesRestantes = $quantidade;
    }

    public function salvar(Amostra $amostra): void
    {
        if ($this->colisoesRestantes > 0) {
            $this->colisoesRestantes--;

            throw CodigoAmostraDuplicadoException::de($amostra->codigo()->valor());
        }

        if ($amostra->id() === null) {
            if ($this->existeCodigo($amostra->codigo()->valor())) {
                throw CodigoAmostraDuplicadoException::de($amostra->codigo()->valor());
            }

            $amostra->definirId($this->proximoId++);
        }

        $this->amostras[$amostra->id()] = $amostra;
    }

    public function buscarPorId(int $id): ?Amostra
    {
        return $this->amostras[$id] ?? null;
    }

    public function listar(?StatusAmostra $status = null, ?TipoAmostra $tipo = null): array
    {
        return array_values(array_filter(
            $this->amostras,
            static fn (Amostra $amostra): bool => ($status === null || $amostra->status() === $status)
                && ($tipo === null || $amostra->tipo() === $tipo)
        ));
    }

    public function proximoSequencial(string $prefixo, int $ano): int
    {
        $inicio = sprintf('%s-%04d-', strtoupper($prefixo), $ano);
        $maior = 0;

        foreach ($this->amostras as $amostra) {
            $codigo = $amostra->codigo()->valor();

            if (str_starts_with($codigo, $inicio)) {
                $maior = max($maior, (int) substr($codigo, -4));
            }
        }

        return $maior + 1;
    }

    private function existeCodigo(string $codigo): bool
    {
        foreach ($this->amostras as $amostra) {
            if ($amostra->codigo()->valor() === $codigo) {
                return true;
            }
        }

        return false;
    }
}
