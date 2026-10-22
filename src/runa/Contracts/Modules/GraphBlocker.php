<?php

namespace Runa\Contracts\Modules;

/** Safe graph diagnostics; paths include the requested root and failing edge. */
final readonly class GraphBlocker
{
    /** @param list<string> $path */
    public function __construct(
        public string $code,
        public array $path,
        public ?string $constraint = null,
        public ?string $actualVersion = null,
    ) {}
}
