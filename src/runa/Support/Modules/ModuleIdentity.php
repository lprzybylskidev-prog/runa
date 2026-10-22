<?php

namespace Runa\Support\Modules;

use InvalidArgumentException;

final readonly class ModuleIdentity
{
    public string $owner;

    public string $code;

    public function __construct(public string $id)
    {
        $parts = explode('.', $id);
        if (count($parts) !== 2 || ! in_array($parts[0], ['runa', 'app'], true) || ! ModuleManifest::validCode($parts[1])) {
            throw new InvalidArgumentException('Invalid module identity.');
        }
        [$this->owner, $this->code] = $parts;
    }

    public function schema(): string
    {
        return $this->owner.'_'.$this->code;
    }

    public function table(string $name): string
    {
        if (strlen($name) > 63 || preg_match('/\A[a-z][a-z0-9_]*\z/D', $name) !== 1) {
            throw new InvalidArgumentException('Module tables require an unqualified PostgreSQL identifier.');
        }

        return $this->schema().'.'.$name;
    }

    public function storagePrefix(): string
    {
        return 'modules/'.$this->owner.'/'.$this->code.'/';
    }
}
