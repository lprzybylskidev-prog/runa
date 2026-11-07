<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

/** Trusted source metadata, never HTTP input. Field grants supplement operation grants. */
final readonly class SettingsAccess
{
    /** @param array<string,string> $readFields
     * @param array<string,string> $writeFields */
    public function __construct(
        public ApiOperation $read, public ApiOperation $write,
        public array $readFields = [], public array $writeFields = [],
        public ?MutationProof $proof = null,
    ) {
        if ($read->permission === null || $write->permission === null) {
            throw new InvalidArgumentException('Settings access requires explicit operation permissions.');
        }
        foreach ([$readFields, $writeFields] as $fields) {
            if (count($fields) > 128) {
                throw new InvalidArgumentException('Settings field access exceeds its limit.');
            }
            foreach ($fields as $key => $permission) {
                if (! is_string($key) || ! is_string($permission)) {
                    throw new InvalidArgumentException('Invalid settings field access.');
                }
                new ApiOperation($key);
                new ApiOperation($write->key, $permission);
            }
        }
    }
}
