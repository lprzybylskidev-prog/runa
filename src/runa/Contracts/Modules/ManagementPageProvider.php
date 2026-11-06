<?php

namespace Runa\Contracts\Modules;

/** Optional additive declaration extension; metadata never grants authority. */
interface ManagementPageProvider extends ManagementProvider
{
    /** @return list<ManagementPage> */
    public function pages(): array;

    /** @return list<ManagementBinding> */
    public function bindings(): array;

    /** @return list<ManagementContribution> */
    public function contributions(): array;

    /** @return list<ManagementWidget> */
    public function widgets(): array;
}
