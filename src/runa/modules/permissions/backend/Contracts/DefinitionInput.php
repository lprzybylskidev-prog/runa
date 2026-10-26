<?php

namespace Runa\Modules\Permissions\Contracts;

use Runa\Contracts\Modules\DictionaryEntry;
use Runa\Contracts\Modules\TrustedPrincipal;

final readonly class DefinitionInput
{
    public array $dictionary;

    public function __construct(
        public string $kind, DictionaryEntry $dictionary, public AssignmentSet $assignments,
        public ?string $publicId = null, public ?int $version = null, public ?string $sourceUser = null,
    ) {
        if (! in_array($kind, ['role', 'preset'], true) || (($publicId === null) !== ($version === null))
            || ($version !== null && $version < 1) || ! preg_match('/\Acustom\.[a-z][a-z0-9_]{0,63}\z/D', $dictionary->code)
            || ($kind === 'role' && ($assignments->roles !== [] || $sourceUser !== null))) {
            throw new \InvalidArgumentException('Invalid custom access definition.');
        }
        $this->dictionary = $dictionary->toRow();
        foreach ([$publicId, $sourceUser] as $id) {
            if ($id !== null) {
                new TrustedPrincipal($id);
            }
        }
        foreach ($dictionary->toRow() as $key => $value) {
            if (mb_strlen($value) > (str_starts_with($key, 'name_') ? 160 : 1000)) {
                throw new \InvalidArgumentException('Dictionary text exceeds its bound.');
            }
        }
        if ($sourceUser !== null && ($publicId !== null || $assignments->roles !== [] || $assignments->permissions !== [])) {
            throw new \InvalidArgumentException('From-user presets are new snapshots without explicit assignments.');
        }
    }
}
