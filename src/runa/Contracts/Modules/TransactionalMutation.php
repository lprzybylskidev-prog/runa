<?php

namespace Runa\Contracts\Modules;

/** Trusted source participant. Never a client callback or guarded API value. */
interface TransactionalMutation
{
    public function definition(): MutationDefinition;

    public function preflight(): void;

    public function lock(): void;

    public function validate(): void;

    public function apply(): void;

    /** @return array<string, mixed> */
    public function result(): array;

    public function refused(string $reason): void;
}
