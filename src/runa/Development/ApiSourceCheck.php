<?php

namespace Runa\Development;

use FilesystemIterator;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Runa\ModuleFramework\Development\ApiBoundary;
use Runa\ModuleFramework\Development\PhpReferences;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Frontend\ModuleFrontend;
use Runa\Support\Modules\Settings\ModuleSettings;
use Runa\Support\Modules\Work\ModuleWork;

/** Development-only source inspection using the existing analysis toolchain. No application boot. */
final class ApiSourceCheck
{
    /** @return list<string> */
    public function check(string $root): array
    {
        $boundary = new ApiBoundary;
        $errors = [];
        foreach (['src/app', 'tests/backend/app'] as $directory) {
            foreach ($this->files($root.'/'.$directory) as $file) {
                $relative = substr($file, strlen($root) + 1);
                $prefix = '';
                if (preg_match('~/modules/([a-z][a-z0-9_]*)/~', $relative, $match)) {
                    $prefix = 'App\\Modules\\'.str_replace(' ', '', ucwords(str_replace('_', ' ', $match[1]))).'\\';
                }
                try {
                    $references = $this->references((string) file_get_contents($file));
                } catch (Error) {
                    $errors[] = $relative.':invalid_php_source';

                    continue;
                }
                foreach ($boundary->references($references, $prefix) as $error) {
                    $errors[] = $relative.':'.$error;
                }
            }
        }
        foreach ($this->files($root.'/src/runa') as $file) {
            // Reserved integrations are checked for every platform caller, including aliases/FQCNs.
            $relative = substr($file, strlen($root) + 1);
            $prefix = 'Runa\\';
            if (preg_match('~src/runa/modules/([a-z][a-z0-9_]*)/~', $relative, $match)) {
                $prefix = 'Runa\\Modules\\'.str_replace(' ', '', ucwords(str_replace('_', ' ', $match[1]))).'\\';
            }
            try {
                $references = $this->references((string) file_get_contents($file));
                $signatures = $this->signatures((string) file_get_contents($file), $relative);
            } catch (Error) {
                $errors[] = $relative.':invalid_php_source';

                continue;
            }
            foreach ($boundary->references($references, $prefix, $relative) as $error) {
                $errors[] = $relative.':'.$error;
            }
            $errors = [...$errors, ...$signatures];
        }

        return array_values(array_unique($errors));
    }

    /** @return list<string> */
    public function signatures(string $source, string $path): array
    {
        $boundary = new ApiBoundary;
        $nodes = (new ParserFactory)->createForHostVersion()->parse($source) ?? [];
        $headers = [];
        foreach ($nodes as $node) {
            $namespace = $node instanceof Node\Stmt\Namespace_ ? (string) $node->name : '';
            $statements = $node instanceof Node\Stmt\Namespace_ ? $node->stmts : [$node];
            $imports = array_filter($statements, static fn (Node $statement): bool => $statement instanceof Node\Stmt\Use_ || $statement instanceof Node\Stmt\GroupUse);
            $headers[$namespace] = ($headers[$namespace] ?? '<?php '.($namespace === '' ? '' : 'namespace '.$namespace.';')).(new Standard)->prettyPrint($imports);
        }
        $nodes = (new NodeTraverser(new NameResolver))->traverse($nodes);
        $finder = new NodeFinder;
        $errors = [];
        foreach ($finder->findInstanceOf($nodes, ClassLike::class) as $class) {
            $symbol = isset($class->namespacedName) ? $class->namespacedName->toString() : '';
            if (! $boundary->isPublic($symbol)) {
                continue;
            }
            $types = [];
            $documentation = (string) $class->getDocComment();
            if ($class instanceof Node\Stmt\Class_) {
                $types = [...($class->extends ? [$class->extends] : []), ...$class->implements];
            } elseif ($class instanceof Node\Stmt\Interface_) {
                $types = $class->extends;
            }
            foreach ($class->stmts as $member) {
                if ($member instanceof Property && $member->isPublic() && $member->type !== null) {
                    $types[] = $member->type;
                }
                if (! $member instanceof ClassMethod || (! $member->isPublic() && ! $member->isProtected())) {
                    continue;
                }
                // Adapter construction is container-owned; DTO and declaration constructors are public.
                if ($member->name->toString() === '__construct' && in_array($symbol, [ModuleApis::class, ModuleFrontend::class, ModuleSettings::class, ModuleWork::class], true)) {
                    continue;
                }
                $documentation .= "\n".(string) $member->getDocComment();
                if ($member->returnType !== null) {
                    $types[] = $member->returnType;
                }
                foreach ($member->params as $parameter) {
                    if ($parameter->type !== null) {
                        $types[] = $parameter->type;
                    }
                }
            }
            foreach ($class->stmts as $member) {
                if ($member instanceof Node\Stmt\TraitUse) {
                    $types = [...$types, ...$member->traits];
                }
            }
            $names = $finder->findInstanceOf($types, Name::class);
            // Retain namespace/import resolution for callback, generic and exception PHPDoc names.
            $namespace = substr($symbol, 0, (int) strrpos($symbol, '\\'));
            $header = $headers[$namespace] ?? '<?php ';
            $documentation = str_replace(['/**', '*/'], '', $documentation);
            $docReferences = (new PhpReferences)->names($header."\n/**\n".$documentation."\n*/", true);
            foreach ($docReferences as $reference) {
                if (str_starts_with(strtolower($reference), 'runa\\') && ! $boundary->isPublic($reference)) {
                    $errors[] = $path.':public_signature_internal_type:'.$reference;
                }
            }
            foreach ($names as $name) {
                if (str_starts_with(strtolower($name->toString()), 'runa\\') && ! $boundary->isPublic($name->toString())) {
                    $errors[] = $path.':public_signature_internal_type:'.$name->toString();
                }
            }
        }

        return array_values(array_unique($errors));
    }

    /** @return list<string> Resolved native PHP names; no execution or class loading. */
    public function references(string $source): array
    {
        $nodes = (new ParserFactory)->createForHostVersion()->parse($source) ?? [];
        $nodes = (new NodeTraverser(new NameResolver))->traverse($nodes);
        $names = [];
        foreach ((new NodeFinder)->findInstanceOf($nodes, Name::class) as $name) {
            $names[] = $name->toString();
        }

        return array_values(array_unique($names));
    }

    /** @return list<string> */
    private function files(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }
        $files = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && ! $file->isLink() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        sort($files);

        return $files;
    }
}
