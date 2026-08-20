<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\StatusAmostra;
use App\Domain\Enum\TipoAmostra;
use App\Domain\Exception\DataConclusaoInvalidaException;
use App\Domain\Exception\DataRecebimentoInvalidaException;
use App\Domain\Exception\ResponsavelTecnicoObrigatorioException;
use App\Domain\Exception\TransicaoInvalidaException;
use App\Domain\ValueObject\CodigoAmostra;
use DateTimeImmutable;

final class Amostra
{
    private function __construct(
        private ?int $id,
        private readonly CodigoAmostra $codigo,
        private readonly TipoAmostra $tipo,
        private StatusAmostra $status,
        private ?string $responsavelTecnico,
        private readonly DateTimeImmutable $dataRecebimento,
        private ?DateTimeImmutable $dataConclusao,
    ) {
    }

    public static function criar(
        CodigoAmostra $codigo,
        TipoAmostra $tipo,
        DateTimeImmutable $dataRecebimento,
        DateTimeImmutable $hoje,
        ?string $responsavelTecnico = null,
    ): self {
        self::garantirRecebimentoNaoFuturo($dataRecebimento, $hoje);

        return new self(
            id: null,
            codigo: $codigo,
            tipo: $tipo,
            status: StatusAmostra::Recebida,
            responsavelTecnico: self::normalizarResponsavel($responsavelTecnico),
            dataRecebimento: $dataRecebimento,
            dataConclusao: null,
        );
    }

    public static function restaurar(
        int $id,
        CodigoAmostra $codigo,
        TipoAmostra $tipo,
        StatusAmostra $status,
        ?string $responsavelTecnico,
        DateTimeImmutable $dataRecebimento,
        ?DateTimeImmutable $dataConclusao,
    ): self {
        return new self(
            id: $id,
            codigo: $codigo,
            tipo: $tipo,
            status: $status,
            responsavelTecnico: $responsavelTecnico,
            dataRecebimento: $dataRecebimento,
            dataConclusao: $dataConclusao,
        );
    }

    public function transicionarPara(StatusAmostra $novoStatus, ?DateTimeImmutable $dataConclusao = null): void
    {
        $this->garantirQueNaoEstaFinalizada();

        if (!$this->status->podeTransicionarPara($novoStatus)) {
            throw TransicaoInvalidaException::de($this->status, $novoStatus);
        }

        match ($novoStatus) {
            StatusAmostra::EmAnalise => $this->garantirResponsavelTecnicoPreenchido(),
            StatusAmostra::Concluida => $this->registrarConclusao($dataConclusao),
            default => null,
        };

        $this->status = $novoStatus;
    }

    public function definirResponsavelTecnico(?string $nome): void
    {
        $this->garantirQueNaoEstaFinalizada();

        $this->responsavelTecnico = self::normalizarResponsavel($nome);
    }

    public function definirId(int $id): void
    {
        if ($this->id !== null) {
            throw new \LogicException('O id da amostra ja foi definido.');
        }

        $this->id = $id;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function codigo(): CodigoAmostra
    {
        return $this->codigo;
    }

    public function tipo(): TipoAmostra
    {
        return $this->tipo;
    }

    public function status(): StatusAmostra
    {
        return $this->status;
    }

    public function responsavelTecnico(): ?string
    {
        return $this->responsavelTecnico;
    }

    public function dataRecebimento(): DateTimeImmutable
    {
        return $this->dataRecebimento;
    }

    public function dataConclusao(): ?DateTimeImmutable
    {
        return $this->dataConclusao;
    }

    private static function garantirRecebimentoNaoFuturo(
        DateTimeImmutable $dataRecebimento,
        DateTimeImmutable $hoje,
    ): void {
        if ($dataRecebimento->format('Y-m-d') > $hoje->format('Y-m-d')) {
            throw DataRecebimentoInvalidaException::noFuturo();
        }
    }

    private function garantirQueNaoEstaFinalizada(): void
    {
        if ($this->status->ehFinal()) {
            throw TransicaoInvalidaException::amostraJaFinalizada($this->status);
        }
    }

    private function garantirResponsavelTecnicoPreenchido(): void
    {
        if ($this->responsavelTecnico === null) {
            throw ResponsavelTecnicoObrigatorioException::paraIniciarAnalise();
        }
    }

    private function registrarConclusao(?DateTimeImmutable $dataConclusao): void
    {
        if ($dataConclusao === null) {
            throw DataConclusaoInvalidaException::obrigatoria();
        }

        if ($dataConclusao->format('Y-m-d') < $this->dataRecebimento->format('Y-m-d')) {
            throw DataConclusaoInvalidaException::anteriorAoRecebimento();
        }

        $this->dataConclusao = $dataConclusao;
    }

    private static function normalizarResponsavel(?string $nome): ?string
    {
        if ($nome === null) {
            return null;
        }

        $nome = trim($nome);

        if ($nome === '') {
            throw ResponsavelTecnicoObrigatorioException::nomeVazio();
        }

        return $nome;
    }
}
