<?php

namespace Runa\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Runa\Support\ApplicationIdentity;

final class ApplicationIdentityResource extends JsonResource
{
    public function __construct(private readonly ApplicationIdentity $identity)
    {
        parent::__construct($identity);
    }

    /** @return array{applicationId: string, applicationName: string, applicationVersion: string, platformVersion: string, logo?: array{src: string, accessibleName: string}} */
    public function toArray(Request $request): array
    {
        return $this->identity->toArray();
    }
}
