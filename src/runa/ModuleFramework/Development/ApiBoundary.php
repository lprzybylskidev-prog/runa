<?php

namespace Runa\ModuleFramework\Development;

/** Source-owned audience policy, not runtime authorization or executable registration. */
final class ApiBoundary
{
    /** @var array{public: list<string>, reserved: array<string, list<string>>, internal_callers: array<string, list<string>>} */
    private array $policy;

    public function __construct()
    {
        /** @var array{public: list<string>, reserved: array<string, list<string>>, internal_callers: array<string, list<string>>} $policy */
        $policy = json_decode((string) file_get_contents(__DIR__.'/api-boundary.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->policy = $policy;
    }

    public function isPublic(string $name): bool
    {
        return in_array(strtolower(ltrim($name, '\\')), array_map(strtolower(...), $this->policy['public']), true);
    }

    /** @return list<string> Safe codes; source paths are relative to the repository, never caller authority. */
    public function php(string $source, string $ownPrefix = '', string $sourcePath = ''): array
    {
        return $this->references((new PhpReferences)->names($source), $ownPrefix, $sourcePath);
    }

    /** @param list<string> $names
     * @return list<string>
     */
    public function references(array $names, string $ownPrefix = '', string $sourcePath = ''): array
    {
        $errors = [];
        foreach ($names as $name) {
            $lower = strtolower($name);
            if (! str_starts_with($lower, 'runa\\')) {
                if ($ownPrefix !== '' && str_starts_with($lower, 'app\\modules\\') && $lower !== strtolower(rtrim($ownPrefix, '\\')) && ! str_starts_with($lower, strtolower($ownPrefix))
                    && ! str_contains($lower, '\\contracts\\')) {
                    $errors[] = 'foreign_module_import';
                }

                continue;
            }
            if (in_array($sourcePath, $this->policy['internal_callers'][$name] ?? [], true)) {
                continue;
            }
            $reserved = null;
            foreach ($this->policy['reserved'] as $symbol => $callers) {
                if (strtolower($symbol) === $lower) {
                    $reserved = $callers;
                    break;
                }
            }
            if ($reserved !== null) {
                if (! in_array($sourcePath, $reserved, true)) {
                    $errors[] = 'reserved_api_import';
                }

                continue;
            }
            // A namespace alias itself grants no permission to its eventual member.
            $publicNamespace = array_any($this->policy['public'], static fn (string $symbol): bool => str_starts_with(strtolower($symbol), $lower.'\\'));
            if ($publicNamespace || $this->isPublic($name) || ($ownPrefix !== '' && str_starts_with($lower, strtolower($ownPrefix)))) {
                continue;
            }
            $errors[] = str_starts_with($lower, 'runa\\modules\\') ? 'foreign_module_import' : 'internal_platform_import';
        }

        return array_values(array_unique($errors));
    }
}
