<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;
use Runa\ModuleFramework\Management\InputValues as Values;

final readonly class MutationDefinition
{
    /**
     * @param  list<MutationAccess>  $access
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
            Values::objects($items, $type, $limit);
        }
        Values::strings($subjects);
        Values::strings($resources);
        if ($access === [] || count($subjects) > 100 || $resources === [] || count($resources) > 32) {
            throw new InvalidArgumentException('Mutation declaration limit.');
        }
        foreach ($subjects as $subject) {
            new TrustedPrincipal($subject);
        }
        foreach ($resources as $resource) {
            if (! str_starts_with($resource, $owner.'.') || strlen($resource) > 256 || preg_match('/\A[a-z0-9_.:-]+\z/D', $resource) !== 1) {
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
