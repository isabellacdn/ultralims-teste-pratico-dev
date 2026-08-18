<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Enum\TipoAmostra;
use DateTimeImmutable;

final readonly class CadastrarAmostraInput
{
    public function __construct(
        public TipoAmostra $tipo,
        public DateTimeImmutable $dataRecebimento,
        public ?string $responsavelTecnico = null,
    ) {
    }
}
