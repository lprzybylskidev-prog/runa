<?php

namespace App\Modules\Reference\Models;

use Runa\Modules\Audit\Contracts\Auditable;
use Runa\Modules\Audit\Contracts\AuditModelPolicy;
use Runa\Support\Modules\Data\ModuleModel;

/** Development example of an owned UUIDv7 entity. */
/** @property string $public_id
 * @property string $code
 * @property int $revision */
final class Sample extends ModuleModel
{
    use Auditable;

    public function auditPolicy(): AuditModelPolicy
    {
        return new AuditModelPolicy('app.reference', 'sample', ['code']);
    }

    protected $table = 'app_reference.samples';

    protected $fillable = ['code'];

    public static function moduleId(): string
    {
        return 'app.reference';
    }
}
