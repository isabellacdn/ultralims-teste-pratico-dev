<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\StatusAmostra;
use App\Domain\Enum\TipoAmostra;
use App\Domain\Exception\DataConclusaoInvalidaException;
use App\Domain\Exception\ResponsavelTecnicoObrigatorioException;
use App\Domain\Exception\TransicaoInvalidaException;
use App\Domain\ValueObject\CodigoAmostra;
use DateTimeImmutable;

/**
 * A amostra do laboratorio.
 *
 * Esta classe e a dona das regras de negocio da secao 2.2 do enunciado. Ela nao
 * conhece banco de dados nem HTTP: e PHP puro. Por isso da para testar as regras
 * sem subir nada.
 *
 * O construtor e privado de proposito. Para nascer, uma amostra passa
 * obrigatoriamente por criar() (amostra nova) ou restaurar() (amostra que veio
 * do banco). Assim nao existe amostra em estado invalido no sistema.
 */
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

    /**
     * Regra 1: toda amostra e criada com status Recebida.
     */
    public static function criar(
        CodigoAmostra $codigo,
        TipoAmostra $tipo,
        DateTimeImmutable $dataRecebimento,
        ?string $responsavelTecnico = null,
    ): self {
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

    /**
     * Reconstroi uma amostra que ja existe (usado pelo repositorio ao ler do banco).
     * Aqui nao ha validacao de transicao: o estado ja aconteceu, so estamos
     * trazendo ele de volta para a memoria.
     */
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

    /**
     * Unico caminho para mudar o status. Todas as regras passam por aqui.
     */
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

    /**
     * Chamado pelo repositorio depois do INSERT, quando o banco devolve o id.
     */
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

    /**
     * Regra 5: Concluida e Rejeitada sao estados finais.
     */
    private function garantirQueNaoEstaFinalizada(): void
    {
        if ($this->status->ehFinal()) {
            throw TransicaoInvalidaException::amostraJaFinalizada($this->status);
        }
    }

    /**
     * Regra 2: so vai para EmAnalise com responsavel tecnico preenchido.
     */
    private function garantirResponsavelTecnicoPreenchido(): void
    {
        if ($this->responsavelTecnico === null) {
            throw ResponsavelTecnicoObrigatorioException::paraIniciarAnalise();
        }
    }

    /**
     * Regra 3: para concluir e preciso data de conclusao, e ela nao pode ser
     * anterior a data de recebimento. (A exigencia de estar em EmAnalise ja foi
     * garantida pelo mapa de transicoes do enum.)
     */
    private function registrarConclusao(?DateTimeImmutable $dataConclusao): void
    {
        if ($dataConclusao === null) {
            throw DataConclusaoInvalidaException::obrigatoria();
        }

        // Comparamos so a parte da data: o enunciado fala em datas, nao em horarios.
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
