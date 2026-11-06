<?php

namespace Runa\Contracts\Modules;

use DateTimeImmutable;
use InvalidArgumentException;
use Runa\ModuleFramework\Management\DeclarationRules as Rules;

final readonly class ManagementWidgetResult
{
    /** @param array<string, mixed> $values */
    public function __construct(public array $values, public ?string $observedAt = null,
        public ?string $expiresAt = null)
    {
        Rules::json($values);
        foreach ([$observedAt, $expiresAt] as $timestamp) {
            if ($timestamp === null) {
                continue;
            }
            if (preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(?:\.[0-9]{1,6})?(?:Z|\+00:00)\z/D', $timestamp) !== 1) {
                throw new InvalidArgumentException('Invalid widget observation.');
            }
            new DateTimeImmutable($timestamp);
            $errors = DateTimeImmutable::getLastErrors();
            if ($errors !== false && ($errors['warning_count'] || $errors['error_count'])) {
                throw new InvalidArgumentException('Invalid widget observation.');
            }
        }
        if ($expiresAt !== null && ($observedAt === null || new DateTimeImmutable($expiresAt) <= new DateTimeImmutable($observedAt))) {
            throw new InvalidArgumentException('Invalid widget expiry.');
        }
    }
}
