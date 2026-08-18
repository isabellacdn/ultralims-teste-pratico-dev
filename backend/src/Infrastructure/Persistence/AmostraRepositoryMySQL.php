<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Amostra;
use App\Domain\Enum\StatusAmostra;
use App\Domain\Enum\TipoAmostra;
use App\Domain\Exception\CodigoAmostraDuplicadoException;
use App\Domain\Repository\AmostraRepositoryInterface;
use App\Domain\ValueObject\CodigoAmostra;
use DateTimeImmutable;
use PDO;
use PDOException;

final readonly class AmostraRepositoryMySQL implements AmostraRepositoryInterface
{
    private const ERRO_CHAVE_DUPLICADA = '23000';

    public function __construct(private PDO $conexao)
    {
    }

    public function salvar(Amostra $amostra): void
    {
        $amostra->id() === null
            ? $this->inserir($amostra)
            : $this->atualizar($amostra);
    }

    public function buscarPorId(int $id): ?Amostra
    {
        $comando = $this->conexao->prepare('SELECT * FROM amostras WHERE id = :id');
        $comando->execute(['id' => $id]);

        $linha = $comando->fetch();

        return $linha === false ? null : $this->paraEntidade($linha);
    }

    public function listar(?StatusAmostra $status = null, ?TipoAmostra $tipo = null): array
    {
        $sql = 'SELECT * FROM amostras';
        $condicoes = [];
        $parametros = [];

        if ($status !== null) {
            $condicoes[] = 'status = :status';
            $parametros['status'] = $status->value;
        }

        if ($tipo !== null) {
            $condicoes[] = 'tipo = :tipo';
            $parametros['tipo'] = $tipo->value;
        }

        if ($condicoes !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $condicoes);
        }

        $sql .= ' ORDER BY id';

        $comando = $this->conexao->prepare($sql);
        $comando->execute($parametros);

        return array_map($this->paraEntidade(...), $comando->fetchAll());
    }

    public function proximoSequencial(string $prefixo, int $ano): int
    {
        $comando = $this->conexao->prepare(
            'SELECT MAX(RIGHT(codigo, 4)) AS maior FROM amostras WHERE codigo LIKE :inicio'
        );
        $comando->execute(['inicio' => sprintf('%s-%04d-%%', strtoupper($prefixo), $ano)]);

        return ((int) $comando->fetchColumn()) + 1;
    }

    private function inserir(Amostra $amostra): void
    {
        $comando = $this->conexao->prepare(
            'INSERT INTO amostras
                (codigo, tipo, status, responsavel_tecnico, data_recebimento, data_conclusao)
             VALUES
                (:codigo, :tipo, :status, :responsavel_tecnico, :data_recebimento, :data_conclusao)'
        );

        try {
            $comando->execute($this->paraLinha($amostra));
        } catch (PDOException $erro) {
            if ($erro->getCode() === self::ERRO_CHAVE_DUPLICADA) {
                throw CodigoAmostraDuplicadoException::de($amostra->codigo()->valor());
            }

            throw $erro;
        }

        $amostra->definirId((int) $this->conexao->lastInsertId());
    }

    private function atualizar(Amostra $amostra): void
    {
        $comando = $this->conexao->prepare(
            'UPDATE amostras SET
                status = :status,
                responsavel_tecnico = :responsavel_tecnico,
                data_conclusao = :data_conclusao
             WHERE id = :id'
        );

        $comando->execute([
            'status' => $amostra->status()->value,
            'responsavel_tecnico' => $amostra->responsavelTecnico(),
            'data_conclusao' => $amostra->dataConclusao()?->format('Y-m-d'),
            'id' => $amostra->id(),
        ]);
    }

    private function paraLinha(Amostra $amostra): array
    {
        return [
            'codigo' => $amostra->codigo()->valor(),
            'tipo' => $amostra->tipo()->value,
            'status' => $amostra->status()->value,
            'responsavel_tecnico' => $amostra->responsavelTecnico(),
            'data_recebimento' => $amostra->dataRecebimento()->format('Y-m-d'),
            'data_conclusao' => $amostra->dataConclusao()?->format('Y-m-d'),
        ];
    }

    private function paraEntidade(array $linha): Amostra
    {
        return Amostra::restaurar(
            (int) $linha['id'],
            CodigoAmostra::de($linha['codigo']),
            TipoAmostra::from($linha['tipo']),
            StatusAmostra::from($linha['status']),
            $linha['responsavel_tecnico'],
            new DateTimeImmutable($linha['data_recebimento']),
            $linha['data_conclusao'] === null ? null : new DateTimeImmutable($linha['data_conclusao']),
        );
    }
}
