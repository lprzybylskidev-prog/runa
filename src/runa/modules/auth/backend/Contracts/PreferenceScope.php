<?php

namespace Runa\Modules\Auth\Contracts;

use Runa\Support\Modules\ModuleIdentity;

final readonly class PreferenceScope
{
    public function __construct(public string $application, public string $module, public string $view, public string $key)
    {
        new ModuleIdentity($module);
        foreach ([$application, $view, $key] as $part) {
            if (! preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9_.-]{0,127}\z/D', $part)) {
                throw new \InvalidArgumentException('Invalid preference scope.');
            }
        }
    }

    public function id(): string
    {
        return implode(':', [$this->application, $this->module, $this->view, $this->key]);
    }
}
