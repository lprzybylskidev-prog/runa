<?php

namespace Runa\Contracts\Modules;

/** Pure metadata only. Declarations never grant authorization or execute actions. */
interface ManagementProvider
{
    /** @return list<ManagementPermission> */
    public function permissions(): array;

    /** @return list<ManagementAction> */
    public function actions(): array;

    /** @return list<ManagementComponent> */
    public function components(): array;
}
