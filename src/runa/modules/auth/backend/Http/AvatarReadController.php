<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Runa\Modules\Auth\Actions\Avatars;

final readonly class AvatarReadController
{
    public function __construct(private Avatars $action) {}

    public function __invoke(Request $request): Response
    {
        $avatar = $this->action->read($request);

        return response($avatar['contents'], 200, ['Content-Type' => $avatar['type'], 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'none'; sandbox", 'Content-Disposition' => 'inline']);
    }
}
