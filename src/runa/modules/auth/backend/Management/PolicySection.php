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
use Runa\Modules\Auth\Contracts\AuthPolicies;
use Runa\Modules\Auth\Definitions\IdentitySettings;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Settings\ModuleSettings;

/** Auth settings retain their own runner and mandatory publication participant. */
final readonly class PolicySection implements ManagementResourceReader, ManagementWriter
{
    public function __construct(private ModuleApis $apis, private ModuleSettings $settings, private OperationGuard $guard) {}

    /** @return list<string> */
    public static function keys(): array
    {
        return array_map(fn ($setting) => $setting->key, array_values(array_filter((new IdentitySettings)->settings(), fn ($setting) => ! $setting->sensitive)));
    }

    public function read(ManagementRead $input): ManagementRecord
    {
        $this->guard->enter('runa.auth', new ApiOperation('policy.read', 'runa.auth.policy.read'));
        $state = $this->settings->read('runa.auth');
        $values = [];
        foreach (self::keys() as $key) {
            $values[$key] = $state['settings'][$key]['value'] ?? null;
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
            $keys = array_keys($input->values);
            sort($keys);
            $expected = self::keys();
            sort($expected);
            if ($input->command !== 'publish' || $keys !== $expected) {
                throw new \InvalidArgumentException('Invalid policy patch.');
            }
            $patch = $input->values;
            $preview = $this->apis->resolve('runa.auth', AuthPolicies::class, '3.0.0')->preview($patch, (int) $parts[1], (int) $parts[2]);
            $changes = $preview['changes'] + ['summary' => $preview['consequences']];

            return new ManagementReview(hash('sha256', serialize([$input->command, $input->values, $preview])), $changes, 'password', true);
        } catch (\InvalidArgumentException|\TypeError) {
            throw new OperationRejected('admin_input_invalid', 'runa.auth', 'policy.preview', 422);
        }
    }

    public function execute(ManagementWrite $input): ManagementOutcome
    {
        $review = $this->preview($input);
        if (! is_string($input->review['fingerprint'] ?? null) || ! hash_equals($review->fingerprint, $input->review['fingerprint'])) {
            throw new OperationRejected('policy_stale', 'runa.auth', 'policy.publish', 409);
        }
        [$revision, $generation] = array_map('intval', explode(':', $input->revision));
        $api = $this->apis->resolve('runa.auth', AuthPolicies::class, '3.0.0');
        $result = $api->publish($input->values, $revision, $generation);
        $status = match ($result['status']) {
            'succeeded' => 'committed', 'pending', 'running' => 'pending', 'recovery_required' => 'recovery_required',
            default => throw new OperationRejected('policy_publication_failed', 'runa.auth', 'policy.publish', 409),
        };

        return new ManagementOutcome($status, $status === 'committed' ? $this->read(new ManagementRead) : null, $result['operation']);
    }
}
