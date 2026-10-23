<?php

namespace Runa\Support\Modules\Api;

/** Module-owned typed proxies implement their public interface using invoke(). */
abstract class GuardedApi
{
    final public function __construct(private readonly ModuleApis $gateway, private readonly string $module, private readonly string $contract, private readonly string $version) {}

    /** @param list<mixed> $arguments */
    final protected function invoke(string $method, array $arguments): mixed
    {
        return $this->gateway->invoke($this->module, $this->contract, $this->version, $method, $arguments);
    }
}
