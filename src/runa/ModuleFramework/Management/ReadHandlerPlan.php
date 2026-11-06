<?php

namespace Runa\ModuleFramework\Management;

use Runa\Contracts\Modules\ManagementReadCall;

/** Internal immutable selection; no business data or client authorization token.
 * @phpstan-type WidgetView array{id:string,title:string|array{pl:string,en:string},component:?array{key:string,owner:string,manifest_hash:?string,build_id:?string},values:array<string,mixed>,observed_at:?string,expires_at:?string,diagnostic:?string}
 * @phpstan-type WidgetMetadata array{owner:string,permission:string,order:int,view:WidgetView}
 */
final readonly class ReadHandlerPlan
{
    /** @param list<ManagementReadCall> $calls
     * @param WidgetMetadata|null $widget Safe widget metadata only. */
    public function __construct(public string $id, public string $fingerprint, public array $calls, public ?array $widget = null, public ?string $diagnostic = null) {}
}
