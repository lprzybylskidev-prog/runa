<?php

namespace Runa\Contracts\Modules;

use Runa\ModuleFramework\Management\DeclarationRules as Rules;

final readonly class ManagementWrite
{
    /** @param array<string, mixed> $values
     * @param array<string, mixed>|null $review */
    public function __construct(
        public string $command, public ?string $publicId, public string $revision,
        public array $values, public ?array $review = null, public bool $confirmed = false,
    ) {
        Rules::key($command);
        Rules::publicId($publicId);
        Rules::revision($revision);
        Rules::json($values);
        if ($review !== null) {
            Rules::json($review);
        }
    }
}
