<?php

namespace Runa\Contracts\Modules;

interface HttpProvider
{
    /** @return list<HttpEndpoint> */
    public function endpoints(): array;

    public function schemaVersion(): int;
}
