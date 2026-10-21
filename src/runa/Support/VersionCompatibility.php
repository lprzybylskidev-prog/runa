<?php

namespace Runa\Support;

use InvalidArgumentException;

/** Deliberately bounded stable-version constraint grammar; not Composer syntax. */
final class VersionCompatibility
{
    public static function validVersion(string $version): bool
    {
        return preg_match('/\A(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)(?:-((?:0|[1-9][0-9]*|[0-9]*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9][0-9]*|[0-9]*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*)?\z/D', $version) === 1;
    }

    public function supports(string $constraint, string $version): bool
    {
        $target = $this->stable($version);
        if ($constraint === '' || strlen($constraint) > 256) {
            throw new InvalidArgumentException('Unsupported Runa compatibility constraint.');
        }
        $alternatives = explode('||', $constraint);
        $supported = false;
        foreach ($alternatives as $alternative) {
            $tokens = preg_split('/\s+/', trim($alternative));
            if ($tokens === false || $tokens === ['']) {
                throw new InvalidArgumentException('Unsupported Runa compatibility constraint.');
            }
            $matches = true;
            foreach ($tokens as $token) {
                if (preg_match('/\A(\^|~|>=|<=|>|<|=)?((?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*))\z/D', $token, $parts) !== 1) {
                    throw new InvalidArgumentException('Unsupported Runa compatibility constraint.');
                }
                $operator = $parts[1] !== '' ? $parts[1] : '=';
                $lower = $this->stable($parts[2]);
                $comparison = $target <=> $lower;
                $match = match ($operator) {
                    '=' => $comparison === 0,
                    '>=' => $comparison >= 0,
                    '<=' => $comparison <= 0,
                    '>' => $comparison > 0,
                    '<' => $comparison < 0,
                    '^', '~' => $comparison >= 0 && $target < $this->upper($lower, $operator),
                };
                $matches = $matches && $match;
            }
            // Parse every branch even after a match; unsupported alternatives must fail.
            $supported = $supported || $matches;
        }

        return $supported;
    }

    /** @return array{int, int, int} */
    private function stable(string $version): array
    {
        if (preg_match('/\A(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\z/D', $version, $parts) !== 1) {
            throw new InvalidArgumentException('Compatibility requires a stable X.Y.Z platform version.');
        }
        $numbers = [];
        foreach (array_slice($parts, 1) as $part) {
            // Bound arithmetic explicitly and reject overflow instead of truncating.
            if (strlen($part) > 9) {
                throw new InvalidArgumentException('Version component exceeds supported bounds.');
            }
            $numbers[] = (int) $part;
        }

        return [$numbers[0], $numbers[1], $numbers[2]];
    }

    /** @param array{int, int, int} $version
     * @return array{int, int, int}
     */
    private function upper(array $version, string $operator): array
    {
        [$major, $minor, $patch] = $version;
        if ($operator === '~') {
            return [$major, $minor + 1, 0];
        }

        return $major > 0 ? [$major + 1, 0, 0] : ($minor > 0 ? [0, $minor + 1, 0] : [0, 0, $patch + 1]);
    }
}
