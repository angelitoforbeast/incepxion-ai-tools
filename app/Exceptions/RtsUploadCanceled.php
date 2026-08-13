<?php

namespace App\Exceptions;

use RuntimeException;

/** Thrown from the parser loop when the user cancels an in-flight RTS upload. */
class RtsUploadCanceled extends RuntimeException
{
}
