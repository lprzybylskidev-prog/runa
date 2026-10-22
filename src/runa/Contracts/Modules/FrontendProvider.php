<?php

namespace Runa\Contracts\Modules;

/** Pure source metadata; permission declarations never grant access. */
interface FrontendProvider
{
    /** @return list<FrontendPage> */
    public function pages(): array;
}
