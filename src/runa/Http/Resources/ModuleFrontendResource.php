<?php

namespace Runa\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Runa\Support\Modules\Frontend\ModuleFrontend;

/** @phpstan-import-type Projection from ModuleFrontend */
final class ModuleFrontendResource extends JsonResource
{
    public static $wrap = null;

    /** @param Projection $view */
    public function __construct(private readonly array $view)
    {
        parent::__construct($view);
    }

    /** @return Projection */
    public function toArray(Request $request): array
    {
        return $this->view;
    }
}
