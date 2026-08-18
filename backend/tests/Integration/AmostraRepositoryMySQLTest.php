<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\Entity\Amostra;
use App\Domain\Enum\StatusAmostra;
use App\Domain\Enum\TipoAmostra;
use App\Domain\Exception\CodigoAmostraDuplicadoException;
use App\Domain\ValueObject\CodigoAmostra;
use App\Infrastructure\Config\ConexaoBanco;
use App\Infrastructure\Persistence\AmostraRepositoryMySQL;
use DateTimeImmutable;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AmostraRepositoryMySQL::class)]
final class AmostraRepositoryMySQLTest extends TestCase
{
    private PDO $conexao;

    private AmostraRepositoryMySQL $repositorio;

    protected function setUp(): void
    {
        try {
            $this->conexao = ConexaoBanco::criar([
                'host' => getenv('DB_HOST') ?: '127.0.0.1',
                'port' => (int) (getenv('DB_PORT') ?: 3307),
                'database' => getenv('DB_DATABASE') ?: 'ultralims',
                'username' => getenv('DB_USERNAME') ?: 'ultralims',
                'password' => getenv('DB_PASSWORD') ?: 'ultralims',
            ]);
        } catch (PDOException $erro) {
            self::markTestSkipped('Banco indisponivel: ' . $erro->getMessage());
        }

        $this->conexao->exec('DELETE FROM amostras');
        $this->repositorio = new AmostraRepositoryMySQL($this->conexao);
    }

    public function testShouldPersistAndRetrieveSample(): void
    {
        $amostra = $this->novaAmostra(1);

        $this->repositorio->salvar($amostra);

        self::assertNotNull($amostra->id());

        $encontrada = $this->repositorio->buscarPorId((int) $amostra->id());

        self::assertSame('ISABELLA-2026-0001', $encontrada?->codigo()->valor());
        self::assertSame(TipoAmostra::Agua, $encontrada?->tipo());
        self::assertSame(StatusAmostra::Recebida, $encontrada?->status());
        self::assertSame('2026-08-10', $encontrada?->dataRecebimento()->format('Y-m-d'));
        self::assertNull($encontrada?->dataConclusao());
    }

    public function testShouldReturnNullWhenSampleDoesNotExist(): void
    {
        self::assertNull($this->repositorio->buscarPorId(9999));
    }

    public function testShouldRejectDuplicatedCode(): void
    {
        $this->repositorio->salvar($this->novaAmostra(1));

        $this->expectException(CodigoAmostraDuplicadoException::class);

        $this->repositorio->salvar($this->novaAmostra(1));
    }

    public function testShouldPersistStatusTransition(): void
    {
        $amostra = $this->novaAmostra(1, 'Ana Souza');
        $this->repositorio->salvar($amostra);

        $amostra->transicionarPara(StatusAmostra::EmAnalise);
        $amostra->transicionarPara(StatusAmostra::Concluida, new DateTimeImmutable('2026-08-15'));
        $this->repositorio->salvar($amostra);

        $recarregada = $this->repositorio->buscarPorId((int) $amostra->id());

        self::assertSame(StatusAmostra::Concluida, $recarregada?->status());
        self::assertSame('2026-08-15', $recarregada?->dataConclusao()?->format('Y-m-d'));
        self::assertSame('Ana Souza', $recarregada?->responsavelTecnico());
    }

    public function testShouldStartSequenceAtOneWhenTableIsEmpty(): void
    {
        self::assertSame(1, $this->repositorio->proximoSequencial('ISABELLA', 2026));
    }

    public function testShouldAdvanceSequencePerYear(): void
    {
        $this->repositorio->salvar($this->novaAmostra(1));
        $this->repositorio->salvar($this->novaAmostra(2));

        self::assertSame(3, $this->repositorio->proximoSequencial('ISABELLA', 2026));
        self::assertSame(1, $this->repositorio->proximoSequencial('ISABELLA', 2027));
    }

    public function testShouldFilterByStatusAndType(): void
    {
        $this->repositorio->salvar($this->novaAmostra(1, null, TipoAmostra::Agua));
        $this->repositorio->salvar($this->novaAmostra(2, null, TipoAmostra::Solo));

        $rejeitada = $this->novaAmostra(3, null, TipoAmostra::Agua);
        $rejeitada->transicionarPara(StatusAmostra::Rejeitada);
        $this->repositorio->salvar($rejeitada);

        self::assertCount(3, $this->repositorio->listar());
        self::assertCount(2, $this->repositorio->listar(StatusAmostra::Recebida));
        self::assertCount(2, $this->repositorio->listar(null, TipoAmostra::Agua));
        self::assertCount(1, $this->repositorio->listar(StatusAmostra::Recebida, TipoAmostra::Agua));
        self::assertSame([], $this->repositorio->listar(StatusAmostra::Concluida));
    }

    private function novaAmostra(
        int $sequencial,
        ?string $responsavelTecnico = null,
        TipoAmostra $tipo = TipoAmostra::Agua,
    ): Amostra {
        return Amostra::criar(
            CodigoAmostra::gerar('ISABELLA', 2026, $sequencial),
            $tipo,
            new DateTimeImmutable('2026-08-10'),
            $responsavelTecnico,
        );
    }
}
