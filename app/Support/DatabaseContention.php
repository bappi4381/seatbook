<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DatabaseContention
{
    public const BUSY_MESSAGE = 'This seat is busy, please try again';

    /**
     * MySQL lock wait timeout / deadlock after transaction retries are exhausted.
     */
    public static function throwIfLockFailure(QueryException $exception): void
    {
        $code = (int) ($exception->errorInfo[1] ?? 0);

        if (in_array($code, [1205, 1213], true)) {
            throw new HttpException(503, self::BUSY_MESSAGE);
        }
    }
}
