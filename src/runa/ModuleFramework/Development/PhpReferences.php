<?php

namespace Runa\ModuleFramework\Development;

/** Lexical names, including grouped imports and namespace aliases; never loads source classes. */
final class PhpReferences
{
    /** @return list<string> */
    public function names(string $source, bool $documentation = false): array
    {
        $tokens = token_get_all($source);
        $namespace = '';
        $aliases = $names = [];
        $count = count($tokens);
        for ($index = 0; $index < $count; $index++) {
            $token = $tokens[$index];
            if (! is_array($token)) {
                continue;
            }
            if ($token[0] === T_NAMESPACE) {
                $namespace = '';
                while (++$index < $count && ! in_array($tokens[$index], [';', '{'], true)) {
                    $part = $tokens[$index];
                    if (is_array($part) && in_array($part[0], [T_STRING, T_NAME_QUALIFIED], true)) {
                        $namespace .= $part[1];
                    }
                }
                $aliases = [];

                continue;
            }
            if ($documentation && $token[0] === T_DOC_COMMENT) {
                preg_match_all('~(?<![a-zA-Z0-9_])\\\\?[A-Z][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*~', $token[1], $matches);
                foreach ($matches[0] as $reference) {
                    $parts = explode('\\', $reference);
                    $alias = $aliases[strtolower($parts[0])] ?? null;
                    if (str_starts_with($reference, '\\')) {
                        $names[] = ltrim($reference, '\\');
                    } elseif ($alias !== null) {
                        $names[] = $alias.(count($parts) > 1 ? '\\'.implode('\\', array_slice($parts, 1)) : '');
                    } elseif (str_contains($reference, '\\')) {
                        $names[] = ($namespace === '' ? '' : $namespace.'\\').$reference;
                    }
                }

                continue;
            }
            if ($token[0] === T_USE) {
                // Closure captures and trait uses do not introduce class aliases.
                $statement = '';
                $cursor = $index;
                while (++$cursor < $count && ! in_array($tokens[$cursor], [';', '('], true)) {
                    $part = $tokens[$cursor];
                    $statement .= is_array($part) ? $part[1] : $part;
                }
                if (($tokens[$cursor] ?? null) === '(') {
                    continue;
                }
                if (! str_contains($statement, '\\')) {
                    continue;
                }
                $prefix = '';
                if (str_contains($statement, '{')) {
                    [$prefix, $statement] = explode('{', $statement, 2);
                    $statement = rtrim(trim($statement), '}');
                }
                foreach (explode(',', $statement) as $import) {
                    if (preg_match('/^(?:function\s+|const\s+)?(\S+)(?:\s+as\s+(\w+))?$/i', trim($import), $match) !== 1) {
                        continue;
                    }
                    $name = ltrim(trim($prefix).$match[1], '\\');
                    $parts = explode('\\', $name);
                    $aliases[strtolower($match[2] ?? end($parts))] = $name;
                    if (! $documentation) {
                        $names[] = $name;
                    }
                }
                $index = $cursor;

                continue;
            }
            if ($documentation) {
                continue;
            }
            if (! in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE], true)) {
                continue;
            }
            $name = $token[1];
            $parts = explode('\\', $name);
            $alias = $aliases[strtolower($parts[0])] ?? null;
            if ($token[0] === T_NAME_FULLY_QUALIFIED) {
                $names[] = ltrim($name, '\\');
            } elseif ($token[0] === T_NAME_RELATIVE) {
                $names[] = $namespace.'\\'.substr($name, strlen('namespace\\'));
            } elseif ($alias !== null) {
                $names[] = $alias.(count($parts) > 1 ? '\\'.implode('\\', array_slice($parts, 1)) : '');
            } elseif ($token[0] === T_NAME_QUALIFIED) {
                $names[] = ($namespace === '' ? '' : $namespace.'\\').$name;
            }
        }

        return array_values(array_unique($names));
    }
}
