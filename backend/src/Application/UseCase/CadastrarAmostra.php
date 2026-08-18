<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\CadastrarAmostraInput;
use App\Application\Exception\FalhaAoGerarCodigoException;
use App\Domain\Entity\Amostra;
use App\Domain\Exception\CodigoAmostraDuplicadoException;
use App\Domain\Repository\AmostraRepositoryInterface;
use App\Domain\ValueObject\CodigoAmostra;

final readonly class CadastrarAmostra
{
    private const MAX_TENTATIVAS = 3;

    public function __construct(
        private AmostraRepositoryInterface $repositorio,
        private string $prefixoCodigo,
    ) {
    }

    public function executar(CadastrarAmostraInput $entrada): Amostra
    {
        $ano = (int) $entrada->dataRecebimento->format('Y');

        for ($tentativa = 1; $tentativa <= self::MAX_TENTATIVAS; $tentativa++) {
            $amostra = Amostra::criar(
                CodigoAmostra::gerar(
                    $this->prefixoCodigo,
                    $ano,
                    $this->repositorio->proximoSequencial($this->prefixoCodigo, $ano),
                ),
                $entrada->tipo,
                $entrada->dataRecebimento,
                $entrada->responsavelTecnico,
            );

            try {
                $this->repositorio->salvar($amostra);

                return $amostra;
            } catch (CodigoAmostraDuplicadoException) {
                continue;
            }
        }

        throw FalhaAoGerarCodigoException::aposTentativas(self::MAX_TENTATIVAS);
    }
}
