<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown from the first batch when too many shipments would move backward, i.e. this looks
 * like an older/wrong export. Control flow only — it stops the import before anything has
 * been written, so the user can confirm or cancel.
 */
class RtsNeedsConfirmation extends RuntimeException
{
    public function __construct(public array $conflicts, public int $conflictCount)
    {
        parent::__construct('Upload needs confirmation.');
    }
}
