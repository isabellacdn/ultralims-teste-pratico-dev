<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use RuntimeException;

/**
 * Base de todas as violacoes de regra de negocio.
 *
 * Ter uma base comum permite que a camada HTTP capture UM tipo so e devolva
 * 422 (Unprocessable Entity), sem precisar conhecer cada excecao especifica.
 */
abstract class RegraDeNegocioException extends RuntimeException
{
}
