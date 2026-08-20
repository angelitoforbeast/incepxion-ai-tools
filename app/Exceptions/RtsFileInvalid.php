<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * User-facing file validation error (missing required columns, unreadable file, etc.).
 * Its message is safe to show to the user — it only references the required J&T columns,
 * never internal/DB details.
 */
class RtsFileInvalid extends RuntimeException
{
}
