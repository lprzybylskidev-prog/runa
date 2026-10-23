<?php

namespace Runa\Contracts\Modules;

/** Trusted code extension; accept bounded JSON values without conversion or side effects. */
interface SettingType
{
    /** @param array<string, mixed> $options */
    public function accepts(mixed $value, array $options): bool;
}
