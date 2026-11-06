<?php

namespace Runa\Contracts\Modules;

use Runa\ModuleFramework\Management\DeclarationRules as Rules;

final readonly class ManagementContribution
{
    public function __construct(
        public string $key, public string $host, public ManagementSection $section, public int $order = 0,
    ) {
        Rules::key($key);
        Rules::key($host);
        if (preg_match('/\A(?:runa\.[a-z][a-z0-9_]*|app(?:\.[a-z][a-z0-9_]*)?)\.[a-z][a-z0-9_]*\.[a-z][a-z0-9_.]*\z/D', $host) !== 1) {
            throw new \InvalidArgumentException('Invalid management host.');
        }
        Rules::presentation($order, null);
    }
}
