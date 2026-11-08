<?php

namespace Runa\Integration\Core;

/** Internal result of a read that required no independent health preparation.
 * @template T */
final readonly class CompletedAdminRead
{
    /** @param T $result */
    public function __construct(public mixed $result) {}
}
