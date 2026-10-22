<?php

namespace Runa\Contracts\Modules;

interface UpgradeHandler
{
    public function run(UpgradeContext $context): void;
}
