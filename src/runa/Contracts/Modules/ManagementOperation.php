<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

/** Declarative safety requirements; backend Actions enforce these independently. */
final readonly class ManagementOperation
{
    /** @param list<string> $inputs Names of declared writable fields; never validation code. */
    public function __construct(
        public ApiOperation $operation, public array $inputs = [], public string $stepUp = 'none',
        public bool $revisionRequired = true, public bool $previewRequired = false, public bool $bulk = false,
    ) {
        if ($operation->permission === null || ! array_is_list($inputs) || count($inputs) > 64
            || count(array_unique($inputs)) !== count($inputs)
            || ! in_array($stepUp, ['none', 'password', 'second_factor', 'both'], true)) {
            throw new InvalidArgumentException('Invalid management operation.');
        }
        foreach ($inputs as $key) {
            new ApiOperation($key);
        }
    }
}
