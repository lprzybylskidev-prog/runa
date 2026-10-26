<?php

namespace Runa\Contracts\Modules;

final readonly class HttpEndpoint
{
    /** Business endpoints only. Module administration remains trusted CLI-only.
     * @param  class-string  $controller  Invokable controller, optionally with a Form Request.
     */
    public function __construct(public string $method, public string $path, public ApiOperation $operation, public string $controller, public HttpAccess $access = HttpAccess::Public) {}
}
