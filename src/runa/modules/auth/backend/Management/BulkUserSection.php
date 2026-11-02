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
use Runa\Modules\Auth\Contracts\AuthBulkUsers;
use Runa\Modules\Auth\Contracts\BulkUserChange;
use Runa\Modules\Auth\Contracts\SessionSelection;
use Runa\Modules\Auth\Support\AdministrationFeatures;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

final readonly class BulkUserSection implements ManagementResourceReader, ManagementWriter
{
    public function __construct(private ModuleApis $apis) {}

    public function read(ManagementRead $input): ManagementRecord
    {
        return new ManagementRecord(null, '0', ['public_ids' => [], 'at' => null, 'enabled' => true, 'summary' => null]);
    }

    public function page(ManagementRead $input): ManagementPageResult
    {
        throw new OperationRejected('admin_operation_unavailable', 'runa.auth', 'users.bulk_preview', 404);
    }

    private function change(ManagementWrite $input): BulkUserChange
    {
        $spec = AdministrationPages::commands()['bulk_users'][$input->command] ?? null;
        $keys = array_keys($input->values);
        sort($keys);
        $expected = $spec['inputs'] ?? [];
        sort($expected);
        if ($spec === null || $input->publicId !== null || $input->revision !== '0' || $keys !== $expected) {
            throw new \InvalidArgumentException('Invalid bulk input.');
        }
        new SessionSelection($input->values['public_ids']);

        return new BulkUserChange($input->command, Moment::seconds($input->values['at'] ?? null), $input->values['enabled'] ?? null);
    }

    public function preview(ManagementWrite $input): ManagementReview
    {
        try {
            $change = $this->change($input);
            $preview = $this->apis->resolve('runa.auth', AuthBulkUsers::class, '1.0.0')->preview(new SessionSelection($input->values['public_ids']), $change);

            $summary = ['accounts' => $preview['accounts']];
            if ($preview['sessions'] > 0) {
                $summary['sessions'] = $preview['sessions'];
            }
            if (app(AdministrationFeatures::class)->enabled('trust') && $preview['trusted_devices'] > 0) {
                $summary['trusted_devices'] = $preview['trusted_devices'];
            }

            return new ManagementReview($preview['fingerprint'], ['summary' => $summary], 'both', true);
        } catch (\InvalidArgumentException|\TypeError) {
            throw new OperationRejected('admin_input_invalid', 'runa.auth', 'users.bulk_preview', 422);
        }
    }

    public function execute(ManagementWrite $input): ManagementOutcome
    {
        $review = $this->preview($input);
        if (! $input->confirmed || ! is_string($input->review['fingerprint'] ?? null) || ! hash_equals($review->fingerprint, $input->review['fingerprint'])) {
            throw new OperationRejected('user_preview_stale', 'runa.auth', 'users.bulk_execute', 409);
        }
        $this->apis->resolve('runa.auth', AuthBulkUsers::class, '1.0.0')->execute(new SessionSelection($input->values['public_ids']), $this->change($input), $review->fingerprint);

        return new ManagementOutcome('committed');
    }
}
