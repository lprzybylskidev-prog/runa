<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

final readonly class MutationDefinition
{
    /** @param list<MutationAccess> $access
     * @param  list<string>  $subjects
     * @param  list<string>  $resources
     * @param  list<MutationProof>  $proofs
     */
    public function __construct(public string $owner, public string $key, public array $access,
        public array $subjects, public array $resources, public array $proofs = [])
    {
        new ApiOperation($key);
        if ($owner !== 'app' && preg_match('/\A(?:runa|app)\.[a-z][a-z0-9_]{0,47}\z/D', $owner) !== 1) {
            throw new InvalidArgumentException('Invalid mutation owner.');
        }
        foreach ([[$access, MutationAccess::class, 32], [$proofs, MutationProof::class, 8]] as [$items, $type, $limit]) {
            if (! array_is_list($items) || count($items) > $limit) {
                throw new InvalidArgumentException('Mutation declaration limit.');
            }
            foreach ($items as $item) {
                if (! $item instanceof $type) {
                    throw new InvalidArgumentException('Invalid mutation declaration.');
                }
            }
        }
        if ($access === [] || ! array_is_list($subjects) || count($subjects) > 100 || ! array_is_list($resources) || $resources === [] || count($resources) > 32) {
            throw new InvalidArgumentException('Mutation declaration limit.');
        }
        foreach ($subjects as $subject) {
            new TrustedPrincipal($subject);
        }
        foreach ($resources as $resource) {
            if (! is_string($resource) || ! str_starts_with($resource, $owner.'.') || strlen($resource) > 256 || preg_match('/\A[a-z0-9_.:-]+\z/D', $resource) !== 1) {
                throw new InvalidArgumentException('Invalid mutation resource.');
            }
        }
        if (count(array_unique($subjects)) !== count($subjects) || count(array_unique($resources)) !== count($resources)) {
            throw new InvalidArgumentException('Duplicate mutation resource or subject.');
        }
        $sorted = $subjects;
        sort($sorted);
        if ($sorted !== $subjects) {
            throw new InvalidArgumentException('Mutation subjects require canonical order.');
        }
    }
}
