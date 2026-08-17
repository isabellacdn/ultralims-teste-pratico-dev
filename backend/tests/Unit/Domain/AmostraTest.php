<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Entity\Amostra;
use App\Domain\Enum\StatusAmostra;
use App\Domain\Enum\TipoAmostra;
use App\Domain\Exception\DataConclusaoInvalidaException;
use App\Domain\Exception\ResponsavelTecnicoObrigatorioException;
use App\Domain\Exception\TransicaoInvalidaException;
use App\Domain\ValueObject\CodigoAmostra;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Testes das regras de negocio da secao 2.2 do enunciado.
 *
 * Repare: nenhum teste aqui usa banco de dados, servidor HTTP ou mock. Sao
 * regras de dominio puro — e por isso rodam em milissegundos.
 */
final class AmostraTest extends TestCase
{
    private const RECEBIMENTO = '2026-08-10';

    // ---------------------------------------------------------------------
    // Regra 1 — toda amostra e criada com status Recebida
    // ---------------------------------------------------------------------

    public function test_amostra_nasce_com_status_recebida(): void
    {
        $amostra = $this->amostraNova();

        self::assertSame(StatusAmostra::Recebida, $amostra->status());
        self::assertNull($amostra->dataConclusao());
        self::assertNull($amostra->id());
    }

    public function test_responsavel_tecnico_e_opcional_na_criacao(): void
    {
        self::assertNull($this->amostraNova()->responsavelTecnico());
        self::assertSame('Ana Souza', $this->amostraNova('Ana Souza')->responsavelTecnico());
    }

    // ---------------------------------------------------------------------
    // Regra 2 — so vai para EmAnalise com responsavel tecnico preenchido
    // ---------------------------------------------------------------------

    public function test_nao_inicia_analise_sem_responsavel_tecnico(): void
    {
        $amostra = $this->amostraNova();

        $this->expectException(ResponsavelTecnicoObrigatorioException::class);

        $amostra->transicionarPara(StatusAmostra::EmAnalise);
    }

    public function test_status_nao_muda_quando_a_transicao_e_recusada(): void
    {
        $amostra = $this->amostraNova();

        try {
            $amostra->transicionarPara(StatusAmostra::EmAnalise);
        } catch (ResponsavelTecnicoObrigatorioException) {
            // esperado
        }

        self::assertSame(StatusAmostra::Recebida, $amostra->status());
    }

    public function test_inicia_analise_com_responsavel_tecnico_informado_na_criacao(): void
    {
        $amostra = $this->amostraNova('Ana Souza');

        $amostra->transicionarPara(StatusAmostra::EmAnalise);

        self::assertSame(StatusAmostra::EmAnalise, $amostra->status());
    }

    public function test_inicia_analise_apos_definir_o_responsavel_tecnico(): void
    {
        $amostra = $this->amostraNova();

        $amostra->definirResponsavelTecnico('Bruno Lima');
        $amostra->transicionarPara(StatusAmostra::EmAnalise);

        self::assertSame(StatusAmostra::EmAnalise, $amostra->status());
        self::assertSame('Bruno Lima', $amostra->responsavelTecnico());
    }

    public function test_responsavel_tecnico_so_com_espacos_e_recusado(): void
    {
        $amostra = $this->amostraNova();

        $this->expectException(ResponsavelTecnicoObrigatorioException::class);

        $amostra->definirResponsavelTecnico('   ');
    }

    // ---------------------------------------------------------------------
    // Regra 3 — Concluida exige EmAnalise + data de conclusao >= recebimento
    // ---------------------------------------------------------------------

    public function test_nao_conclui_direto_a_partir_de_recebida(): void
    {
        $amostra = $this->amostraNova('Ana Souza');

        $this->expectException(TransicaoInvalidaException::class);

        $amostra->transicionarPara(StatusAmostra::Concluida, new DateTimeImmutable('2026-08-15'));
    }

    public function test_nao_conclui_sem_data_de_conclusao(): void
    {
        $amostra = $this->amostraEmAnalise();

        $this->expectException(DataConclusaoInvalidaException::class);

        $amostra->transicionarPara(StatusAmostra::Concluida);
    }

    public function test_nao_conclui_com_data_anterior_ao_recebimento(): void
    {
        $amostra = $this->amostraEmAnalise();

        $this->expectException(DataConclusaoInvalidaException::class);

        $amostra->transicionarPara(StatusAmostra::Concluida, new DateTimeImmutable('2026-08-09'));
    }

    public function test_conclui_com_data_igual_a_de_recebimento(): void
    {
        $amostra = $this->amostraEmAnalise();

        $amostra->transicionarPara(StatusAmostra::Concluida, new DateTimeImmutable(self::RECEBIMENTO));

        self::assertSame(StatusAmostra::Concluida, $amostra->status());
        self::assertSame(self::RECEBIMENTO, $amostra->dataConclusao()?->format('Y-m-d'));
    }

    public function test_conclui_com_data_posterior_a_de_recebimento(): void
    {
        $amostra = $this->amostraEmAnalise();

        $amostra->transicionarPara(StatusAmostra::Concluida, new DateTimeImmutable('2026-08-15'));

        self::assertSame(StatusAmostra::Concluida, $amostra->status());
        self::assertSame('2026-08-15', $amostra->dataConclusao()?->format('Y-m-d'));
    }

    // ---------------------------------------------------------------------
    // Regra 4 — Rejeitada a partir de Recebida ou EmAnalise, nunca de Concluida
    // ---------------------------------------------------------------------

    public function test_rejeita_a_partir_de_recebida(): void
    {
        $amostra = $this->amostraNova();

        $amostra->transicionarPara(StatusAmostra::Rejeitada);

        self::assertSame(StatusAmostra::Rejeitada, $amostra->status());
    }

    public function test_rejeita_a_partir_de_em_analise(): void
    {
        $amostra = $this->amostraEmAnalise();

        $amostra->transicionarPara(StatusAmostra::Rejeitada);

        self::assertSame(StatusAmostra::Rejeitada, $amostra->status());
    }

    public function test_nao_rejeita_a_partir_de_concluida(): void
    {
        $amostra = $this->amostraConcluida();

        $this->expectException(TransicaoInvalidaException::class);

        $amostra->transicionarPara(StatusAmostra::Rejeitada);
    }

    // ---------------------------------------------------------------------
    // Regra 5 — Concluida e Rejeitada sao estados finais
    // ---------------------------------------------------------------------

    #[DataProvider('todosOsStatus')]
    public function test_amostra_concluida_nao_aceita_nenhuma_transicao(StatusAmostra $destino): void
    {
        $amostra = $this->amostraConcluida();

        $this->expectException(TransicaoInvalidaException::class);

        $amostra->transicionarPara($destino, new DateTimeImmutable('2026-08-20'));
    }

    #[DataProvider('todosOsStatus')]
    public function test_amostra_rejeitada_nao_aceita_nenhuma_transicao(StatusAmostra $destino): void
    {
        $amostra = $this->amostraRejeitada();

        $this->expectException(TransicaoInvalidaException::class);

        $amostra->transicionarPara($destino, new DateTimeImmutable('2026-08-20'));
    }

    public function test_amostra_finalizada_nao_aceita_troca_de_responsavel_tecnico(): void
    {
        $amostra = $this->amostraConcluida();

        $this->expectException(TransicaoInvalidaException::class);

        $amostra->definirResponsavelTecnico('Carla Dias');
    }

    /**
     * @return iterable<string, array{StatusAmostra}>
     */
    public static function todosOsStatus(): iterable
    {
        foreach (StatusAmostra::cases() as $status) {
            yield $status->value => [$status];
        }
    }

    // ---------------------------------------------------------------------
    // Auxiliares — deixam cada teste focado no que ele realmente verifica
    // ---------------------------------------------------------------------

    private function amostraNova(?string $responsavelTecnico = null): Amostra
    {
        return Amostra::criar(
            CodigoAmostra::gerar('ISABELLA', 2026, 1),
            TipoAmostra::Agua,
            new DateTimeImmutable(self::RECEBIMENTO),
            $responsavelTecnico,
        );
    }

    private function amostraEmAnalise(): Amostra
    {
        $amostra = $this->amostraNova('Ana Souza');
        $amostra->transicionarPara(StatusAmostra::EmAnalise);

        return $amostra;
    }

    private function amostraConcluida(): Amostra
    {
        $amostra = $this->amostraEmAnalise();
        $amostra->transicionarPara(StatusAmostra::Concluida, new DateTimeImmutable('2026-08-15'));

        return $amostra;
    }

    private function amostraRejeitada(): Amostra
    {
        $amostra = $this->amostraNova();
        $amostra->transicionarPara(StatusAmostra::Rejeitada);

        return $amostra;
    }
}
