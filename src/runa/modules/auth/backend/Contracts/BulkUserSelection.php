<?php

namespace Runa\Modules\Auth\Contracts;

/** Neutral trusted integration; Permissions authorizes selection without Auth importing its internals. */
interface BulkUserSelection
{
    public function authorize(SessionSelection $selection): void;
}
