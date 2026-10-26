<?php

namespace Tests\Fixtures;

use Runa\Modules\Audit\Contracts\Auditable;
use Runa\Modules\Audit\Contracts\AuditModelPolicy;
use Runa\Support\Modules\Data\ModuleModel;

class AuditCaptureFixture extends ModuleModel
{
    use Auditable;

    protected $table = 'app_audit_fixture.records';

    protected $guarded = ['id'];

    public bool $snapshotCapture = false;

    public array $captureEvents = ['created', 'updated', 'deleted', 'restored', 'force_deleted'];

    public static function moduleId(): string
    {
        return 'app.audit_fixture';
    }

    public function auditPolicy(): AuditModelPolicy
    {
        return new AuditModelPolicy('app.audit_fixture', 'record', ['title', 'details', 'deleted_at'],
            events: $this->captureEvents, snapshot: $this->snapshotCapture, sensitiveFields: ['concealed']);
    }
}
