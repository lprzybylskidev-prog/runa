<?php

namespace Runa\Contracts\Modules;

/** Module-local permission keys only; declarations never assign users. */
final readonly class RoleDefinition
{
    /** @param list<string> $permissions */
    public function __construct(public DictionaryEntry $dictionary, public array $permissions)
    {
        if (str_contains($dictionary->code, '.') || ! array_is_list($permissions)
            || count($permissions) > 128 || count(array_unique($permissions)) !== count($permissions)) {
            throw new \InvalidArgumentException('Invalid predefined role.');
        }
        foreach ($permissions as $permission) {
            new ApiOperation($permission);
        }
    }
}
