<?php

namespace Runa\Modules\Auth\Contracts;

/** Trusted internal adapter seam. Critical mail always retains the direct fallback. */
interface SecurityMailDelivery
{
    /** True means accepted; false means unavailable before any send was attempted. */
    public function send(string $recipient, string $subject, string $body): bool;
}
