<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Runa\Contracts\Modules\OperationContext;
use Runa\Modules\Auth\Actions\Avatars;

final readonly class AvatarReadController
{
    public function __construct(private Avatars $action) {}

    public function __invoke(Request $request): Response
    {
        $context = $request->attributes->get('runa.operation');
        $avatar = $this->action->read($request, $context instanceof OperationContext && $context->operation === 'avatar.admin_read');

        return response($avatar['contents'], 200, ['Content-Type' => $avatar['type'], 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'none'; sandbox", 'Content-Disposition' => 'inline']);
    }
}
