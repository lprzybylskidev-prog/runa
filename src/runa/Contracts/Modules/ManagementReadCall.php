<?php

namespace Runa\Contracts\Modules;

/** One source-declared public invocation occurrence; never business arguments or a callback. */
final readonly class ManagementReadCall
{
    public function __construct(public string $module, public string $contract, public string $version, public string $method)
    {
        new ManagementApiTarget($module, $contract, $version, [$method]);
    }
}
