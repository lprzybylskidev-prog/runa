<?php

namespace Runa\Modules\Auth\Management;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ManagementOutcome;
use Runa\Contracts\Modules\ManagementPageResult;
use Runa\Contracts\Modules\ManagementRead;
use Runa\Contracts\Modules\ManagementRecord;
use Runa\Contracts\Modules\ManagementResourceReader;
use Runa\Contracts\Modules\ManagementReview;
use Runa\Contracts\Modules\ManagementWrite;
use Runa\Contracts\Modules\ManagementWriter;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\Modules\Auth\Actions\Policies;
use Runa\Modules\Auth\Contracts\AuthPolicies;
use Runa\Modules\Auth\Definitions\IdentitySettings;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Settings\ModuleSettings;

/** Auth settings retain their own runner and mandatory publication participant. */
abstract readonly class PolicySection implements ManagementResourceReader, ManagementWriter
{
    protected const GROUP = 0;

    public function __construct(private ModuleApis $apis, private ModuleSettings $settings, private Policies $policies, private OperationGuard $guard) {}

    public static function keys(int $group): array
    {
        return array_map(fn ($setting) => $setting->key, array_slice(array_values(array_filter((new IdentitySettings)->settings(), fn ($setting) => ! $setting->sensitive)), $group * 30, 30));
    }

    public function read(ManagementRead $input): ManagementRecord
    {
        $this->guard->enter('runa.auth', new ApiOperation('policy.history', 'runa.auth.policy.read'));
        $state = $this->settings->read('runa.auth');
        $values = [];
        if (static::GROUP === -1) {
            if ($input->cursor !== null && preg_match('/\A[1-9][0-9]{0,8}\z/D', $input->cursor) !== 1) {
                throw new OperationRejected('admin_input_invalid', 'runa.auth', 'policy.history', 422);
            }
            $history = $this->apis->resolve('runa.auth', AuthPolicies::class, '2.0.0')->history($input->cursor === null ? null : (int) $input->cursor);
            $values = ['history' => json_encode($history, JSON_THROW_ON_ERROR), 'source_revision' => null, 'summary' => null];
        } else {
            foreach (self::keys(static::GROUP) as $key) {
                $values[$key] = $state['settings'][$key]['value'] ?? null;
            }
        }
        $values['summary'] = null;

        return new ManagementRecord(null, $state['revision'].':'.$state['generation'], $values);
    }

    public function page(ManagementRead $input): ManagementPageResult
    {
        throw new OperationRejected('admin_operation_unavailable', 'runa.auth', 'policy.history', 404);
    }

    public function preview(ManagementWrite $input): ManagementReview
    {
        try {
            if ($input->publicId !== null || preg_match('/\A([0-9]{1,9}):([0-9]{1,9})\z/D', $input->revision, $parts) !== 1) {
                throw new \InvalidArgumentException('Invalid policy revision.');
            }
            if (static::GROUP === -1) {
                if ($input->command !== 'restore' || array_keys($input->values) !== ['source_revision'] || ! is_int($input->values['source_revision']) || $input->values['source_revision'] < 1) {
                    throw new \InvalidArgumentException('Invalid restore source.');
                }
                // Owner-private immutable history lookup, never a foreign engine or HTTP-selected class.
                $this->guard->enter('runa.auth', new ApiOperation('policy.restore', 'runa.auth.policy.write'));
                $patch = $this->policies->restoreValues($input->values['source_revision']);
            } else {
                $keys = array_keys($input->values);
                sort($keys);
                $expected = self::keys(static::GROUP);
                sort($expected);
                if ($input->command !== 'publish' || $keys !== $expected) {
                    throw new \InvalidArgumentException('Invalid policy patch.');
                }
                $patch = $input->values;
            }
            $preview = $this->apis->resolve('runa.auth', AuthPolicies::class, '2.0.0')->preview($patch, (int) $parts[1], (int) $parts[2]);
            $changes = static::GROUP === -1 ? ['summary' => $preview] : $preview['changes'] + ['summary' => $preview['consequences']];

            return new ManagementReview(hash('sha256', serialize([$input->command, $input->values, $preview])), $changes, 'password', true);
        } catch (\InvalidArgumentException|\TypeError) {
            throw new OperationRejected('admin_input_invalid', 'runa.auth', 'policy.preview', 422);
        }
    }

    public function execute(ManagementWrite $input): ManagementOutcome
    {
        $review = $this->preview($input);
        if ((static::GROUP === -1 && ! $input->confirmed) || ! is_string($input->review['fingerprint'] ?? null) || ! hash_equals($review->fingerprint, $input->review['fingerprint'])) {
            throw new OperationRejected('policy_stale', 'runa.auth', 'policy.publish', 409);
        }
        [$revision, $generation] = array_map('intval', explode(':', $input->revision));
        $api = $this->apis->resolve('runa.auth', AuthPolicies::class, '2.0.0');
        $result = static::GROUP === -1 ? $api->restore($input->values['source_revision'], $revision, $generation) : $api->publish($input->values, $revision, $generation);
        $status = match ($result['status']) {
            'succeeded' => 'committed', 'pending', 'running' => 'pending', 'recovery_required' => 'recovery_required',
            default => throw new OperationRejected('policy_publication_failed', 'runa.auth', 'policy.publish', 409),
        };

        return new ManagementOutcome($status, $status === 'committed' ? $this->read(new ManagementRead) : null, $result['operation']);
    }
}
