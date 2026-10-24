<?php

namespace Runa\Modules\Permissions\Contracts;

use Runa\Contracts\Modules\TrustedPrincipal;

/** Ordinary assigned references, never flattened effective grants or protected roles. */
final readonly class AssignmentSet
{
    public array $roles;

    public array $permissions;

    /** @param list<string> $roles
     * @param  list<string>  $permissions
     */
    public function __construct(array $roles = [], array $permissions = [])
    {
        foreach ([$roles, $permissions] as $values) {
            if (! array_is_list($values) || count($values) > 100) {
                throw new \InvalidArgumentException('Assignment lists must contain at most 100 public references.');
            }
            foreach ($values as $value) {
                if (! is_string($value)) {
                    throw new \InvalidArgumentException('Assignment references must be UUIDv7 strings.');
                }
                new TrustedPrincipal($value);
            }
        }
        $roles = array_values(array_unique($roles));
        $permissions = array_values(array_unique($permissions));
        sort($roles);
        sort($permissions);
        $this->roles = $roles;
        $this->permissions = $permissions;
    }

    public function toArray(): array
    {
        return ['roles' => $this->roles, 'permissions' => $this->permissions];
    }
}
