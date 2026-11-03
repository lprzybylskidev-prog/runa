<?php

namespace Runa\Modules\Auth\Management;

use Runa\Contracts\Modules\ManagementOutcome;
use Runa\Contracts\Modules\ManagementPageResult;
use Runa\Contracts\Modules\ManagementRead;
use Runa\Contracts\Modules\ManagementRecord;
use Runa\Contracts\Modules\ManagementResourceReader;
use Runa\Contracts\Modules\ManagementReview;
use Runa\Contracts\Modules\ManagementWrite;
use Runa\Contracts\Modules\ManagementWriter;
use Runa\Modules\Auth\Contracts\AuthBulkSessions;
use Runa\Modules\Auth\Contracts\SessionSelection;
use Runa\Modules\Auth\Support\AdministrationFeatures;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

final readonly class BulkSessionSection implements ManagementResourceReader, ManagementWriter
{
    public function __construct(private ModuleApis $apis) {}

    public function read(ManagementRead $input): ManagementRecord
    {
        return new ManagementRecord(null, '0', ['public_ids' => [], 'trust' => false, 'summary' => null]);
    }

    public function page(ManagementRead $input): ManagementPageResult
    {
        throw new OperationRejected('admin_operation_unavailable', 'runa.auth', 'sessions.bulk_preview', 404);
    }

    public function preview(ManagementWrite $input): ManagementReview
    {
        try {
            $keys = array_keys($input->values);
            sort($keys);
            $expected = AdministrationPages::commands()['bulk_sessions']['revoke']['inputs'];
            sort($expected);
            if ($input->command !== 'revoke' || $input->publicId !== null || $input->revision !== '0' || $keys !== $expected || ! is_bool($input->values['trust'] ?? false)) {
                throw new \InvalidArgumentException('Invalid bulk input.');
            }
            $preview = $this->apis->resolve('runa.auth', AuthBulkSessions::class, '1.0.0')->preview(new SessionSelection($input->values['public_ids']), $input->values['trust'] ?? false);

            $affected = count(array_filter($preview['accounts'], fn ($account) => $account['sessions'] !== [] || $account['trusted_devices'] !== []));

            return new ManagementReview($preview['fingerprint'], ['summary' => ['accounts' => count($preview['accounts']), 'affected_accounts' => $affected, 'sessions' => $preview['sessions'], ...(app(AdministrationFeatures::class)->enabled('trust') ? ['trusted_devices' => $preview['trusted_devices']] : [])]], 'none', true);
        } catch (\InvalidArgumentException|\TypeError) {
            throw new OperationRejected('admin_input_invalid', 'runa.auth', 'sessions.bulk_preview', 422);
        }
    }

    public function execute(ManagementWrite $input): ManagementOutcome
    {
        $preview = $this->preview($input);
        if (! $input->confirmed || ! is_string($input->review['fingerprint'] ?? null) || ! hash_equals($preview->fingerprint, $input->review['fingerprint'])) {
            throw new OperationRejected('session_preview_stale', 'runa.auth', 'sessions.bulk_revoke', 409);
        }
        $this->apis->resolve('runa.auth', AuthBulkSessions::class, '1.0.0')->revoke(new SessionSelection($input->values['public_ids']), $input->values['trust'] ?? false, $preview->fingerprint);

        return new ManagementOutcome('committed');
    }
}
