<?php

namespace Runa\Modules\Permissions\Contracts;

use Runa\Contracts\Modules\TrustedPrincipal;

/** Ordinary assigned references, never flattened effective grants or protected roles. */
final readonly class AssignmentSet
{
    /** @var list<string> */
    public array $roles;

    /** @var list<string> */
    public array $permissions;

    /** @param array<array-key, mixed> $roles
     * @param  array<array-key, mixed>  $permissions
     */
    public function __construct(array $roles = [], array $permissions = [])
    {
        self::validate($roles);
        self::validate($permissions);
        $roles = array_values(array_unique($roles));
        $permissions = array_values(array_unique($permissions));
        sort($roles);
        sort($permissions);
        $this->roles = $roles;
        $this->permissions = $permissions;
    }

    /** @param array<array-key, mixed> $values
     * @phpstan-assert list<string> $values
     */
    private static function validate(array $values): void
    {
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

    /**
     * @return array{roles: list<string>, permissions: list<string>}
     */
    public function toArray(): array
    {
        return ['roles' => $this->roles, 'permissions' => $this->permissions];
    }
}
