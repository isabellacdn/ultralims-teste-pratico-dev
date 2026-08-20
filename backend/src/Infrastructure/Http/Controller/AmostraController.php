<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\DTO\AmostraOutput;
use App\Application\DTO\CadastrarAmostraInput;
use App\Application\DTO\TransicionarStatusInput;
use App\Application\UseCase\CadastrarAmostra;
use App\Application\UseCase\ConsultarAmostra;
use App\Application\UseCase\ListarAmostras;
use App\Application\UseCase\TransicionarStatusAmostra;
use App\Domain\Entity\Amostra;
use App\Domain\Enum\StatusAmostra;
use App\Domain\Enum\TipoAmostra;
use App\Infrastructure\Http\Middleware\RequisicaoInvalidaException;
use App\Infrastructure\Http\Respostas;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AmostraController
{
    public function __construct(
        private CadastrarAmostra $cadastrarAmostra,
        private ListarAmostras $listarAmostras,
        private ConsultarAmostra $consultarAmostra,
        private TransicionarStatusAmostra $transicionarStatus,
    ) {
    }

    public function cadastrar(ServerRequestInterface $requisicao, ResponseInterface $resposta): ResponseInterface
    {
        $corpo = $this->corpo($requisicao);

        $amostra = $this->cadastrarAmostra->executar(new CadastrarAmostraInput(
            $this->tipoObrigatorio($corpo),
            $this->dataObrigatoria($corpo, 'data_recebimento'),
            $this->textoOpcional($corpo, 'responsavel_tecnico'),
        ));

        return Respostas::json($resposta, AmostraOutput::deEntidade($amostra)->paraArray(), 201);
    }

    public function listar(ServerRequestInterface $requisicao, ResponseInterface $resposta): ResponseInterface
    {
        $filtros = $requisicao->getQueryParams();

        $amostras = $this->listarAmostras->executar(
            $this->statusOpcional($filtros, 'status'),
            $this->tipoOpcional($filtros, 'tipo'),
        );

        return Respostas::json($resposta, array_map(
            static fn (Amostra $amostra): array => AmostraOutput::deEntidade($amostra)->paraArray(),
            $amostras,
        ));
    }

    public function consultar(ServerRequestInterface $requisicao, ResponseInterface $resposta, array $rota): ResponseInterface
    {
        $amostra = $this->consultarAmostra->executar((int) $rota['id']);

        return Respostas::json($resposta, AmostraOutput::deEntidade($amostra)->paraArray());
    }

    public function transicionarStatus(ServerRequestInterface $requisicao, ResponseInterface $resposta, array $rota): ResponseInterface
    {
        $corpo = $this->corpo($requisicao);

        $amostra = $this->transicionarStatus->executar(new TransicionarStatusInput(
            (int) $rota['id'],
            $this->statusObrigatorio($corpo),
            $this->dataOpcional($corpo, 'data_conclusao'),
            $this->textoOpcional($corpo, 'responsavel_tecnico'),
        ));

        return Respostas::json($resposta, AmostraOutput::deEntidade($amostra)->paraArray());
    }

    private function corpo(ServerRequestInterface $requisicao): array
    {
        $corpo = $requisicao->getParsedBody();

        return is_array($corpo) ? $corpo : [];
    }

    private function tipoObrigatorio(array $dados): TipoAmostra
    {
        return $this->tipoOpcional($dados, 'tipo')
            ?? throw RequisicaoInvalidaException::campoObrigatorio('tipo');
    }

    private function tipoOpcional(array $dados, string $campo): ?TipoAmostra
    {
        $valor = $this->textoOpcional($dados, $campo);

        if ($valor === null) {
            return null;
        }

        return TipoAmostra::tryFrom($valor)
            ?? throw RequisicaoInvalidaException::valorInvalido($campo, $this->valoresDe(TipoAmostra::cases()));
    }

    private function statusObrigatorio(array $dados): StatusAmostra
    {
        return $this->statusOpcional($dados, 'status')
            ?? throw RequisicaoInvalidaException::campoObrigatorio('status');
    }

    private function statusOpcional(array $dados, string $campo): ?StatusAmostra
    {
        $valor = $this->textoOpcional($dados, $campo);

        if ($valor === null) {
            return null;
        }

        return StatusAmostra::tryFrom($valor)
            ?? throw RequisicaoInvalidaException::valorInvalido($campo, $this->valoresDe(StatusAmostra::cases()));
    }

    private function dataObrigatoria(array $dados, string $campo): DateTimeImmutable
    {
        return $this->dataOpcional($dados, $campo)
            ?? throw RequisicaoInvalidaException::campoObrigatorio($campo);
    }

    private function dataOpcional(array $dados, string $campo): ?DateTimeImmutable
    {
        $valor = $this->textoOpcional($dados, $campo);

        if ($valor === null) {
            return null;
        }

        $data = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);

        if ($data === false) {
            throw RequisicaoInvalidaException::dataInvalida($campo);
        }

        if ($data->format('Y-m-d') !== $valor) {
            throw RequisicaoInvalidaException::dataInexistente($campo);
        }

        return $data;
    }

    private function textoOpcional(array $dados, string $campo): ?string
    {
        if (!isset($dados[$campo]) || !is_string($dados[$campo])) {
            return null;
        }

        $valor = trim($dados[$campo]);

        return $valor === '' ? null : $valor;
    }

    private function valoresDe(array $casos): string
    {
        return implode(', ', array_map(static fn ($caso): string => $caso->value, $casos));
    }
}
