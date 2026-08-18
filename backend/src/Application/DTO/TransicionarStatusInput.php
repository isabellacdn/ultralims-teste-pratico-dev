<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Enum\StatusAmostra;
use DateTimeImmutable;

final readonly class TransicionarStatusInput
{
    public function __construct(
        public int $id,
        public StatusAmostra $novoStatus,
        public ?DateTimeImmutable $dataConclusao = null,
        public ?string $responsavelTecnico = null,
    ) {
    }
}
