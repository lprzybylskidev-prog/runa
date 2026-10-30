<?php

namespace Runa\Contracts\Modules;

use Runa\ModuleFramework\Management\DeclarationRules as Rules;

final readonly class ManagementPage
{
    /** @param list<ManagementSection> $sections */
    public function __construct(
        public string $key, public string $title, public string $permission,
        public array $sections, public int $order = 0, public ?string $group = null,
        public bool $default = false, public ?string $component = null, public int $schemaVersion = 1,
    ) {
        Rules::key($key);
        if (preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $key) !== 1) {
            throw new \InvalidArgumentException('Invalid management page key.');
        }
        Rules::text($title);
        Rules::permission($permission);
        Rules::collection($sections, ManagementSection::class, 32);
        Rules::presentation($order, $component, $schemaVersion);
        if ($group !== null) {
            Rules::text($group);
        }
    }
}
