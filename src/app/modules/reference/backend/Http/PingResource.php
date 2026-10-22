<?php

namespace App\Modules\Reference\Http;

use App\Modules\Reference\Contracts\PingResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PingResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(private readonly PingResult $result)
    {
        parent::__construct($result);
    }

    /** @return array{message: string} */
    public function toArray(Request $request): array
    {
        return ['message' => $this->result->message];
    }
}
