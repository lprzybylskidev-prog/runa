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
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\Modules\Auth\Contracts\AuthAdministration;
use Runa\Modules\Auth\Contracts\AuthIdentities;
use Runa\Modules\Auth\Contracts\AuthInvitations;
use Runa\Modules\Auth\Contracts\AuthLifecycle;
use Runa\Modules\Auth\Contracts\AuthPolicies;
use Runa\Modules\Auth\Contracts\AuthPreferences;
use Runa\Modules\Auth\Contracts\AuthProfiles;
use Runa\Modules\Auth\Contracts\AuthSecurity;
use Runa\Modules\Auth\Contracts\AuthSessions;
use Runa\Modules\Auth\Contracts\IdentityConflict;
use Runa\Modules\Auth\Contracts\LifecycleChange;
use Runa\Modules\Auth\Contracts\NewIdentity;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Settings\ModuleSettings;

/** Fixed source-owned dispatch. All writes go through the owning guarded public API. */
abstract readonly class SubjectSection implements ManagementResourceReader, ManagementWriter
{
    protected const KEY = '';

    public function __construct(private ModuleApis $apis, private PrincipalScope $principals, private ModuleSettings $settings) {}

    public function read(ManagementRead $input): ManagementRecord
    {
        if (static::KEY === 'creation') {
            return new ManagementRecord(null, '0', ['email' => null, 'name' => null, 'first_name' => null, 'surname' => null, 'login' => null, 'active' => false, 'approved' => false]);
        }
        $id = $input->publicId ?? $this->principals->current()?->publicId ?? throw new OperationRejected('authentication_required', 'runa.auth', 'administration.read', 401);
        if ($input->cursor !== null) {
            if (! in_array(static::KEY, ['sessions', 'trust'], true)) {
                throw new OperationRejected('admin_input_invalid', 'runa.auth', 'administration.read', 422);
            }
            try {
                new TrustedPrincipal($input->cursor);
            } catch (\InvalidArgumentException) {
                throw new OperationRejected('admin_input_invalid', 'runa.auth', 'administration.read', 422);
            }
        }
        $values = [];
        if (static::KEY === 'lifecycle') {
            $values = $this->api(AuthAdministration::class)->lifecycle($id);
            $version = $values['version'];
            unset($values['version'], $values['public_id']);
            $values += ['operation' => 'activate', 'at' => null, 'enabled' => false];
        } elseif (in_array(static::KEY, ['primary_email', 'secondary', 'preferences'], true)) {
            $profile = $this->api(AuthAdministration::class)->profile($id);
            $version = $profile->version;
            $values = match (static::KEY) {
                'primary_email' => ['current_email' => $profile->primaryEmail['email'] ?? null, 'verified' => $profile->primaryEmail['verified'] ?? null,
                    'verification_required' => $profile->primaryEmail['verification_required'] ?? null, 'email' => null, 'replacement_email' => null],
                'secondary' => ['addresses' => json_encode($profile->secondaryEmails, JSON_THROW_ON_ERROR), 'address_id' => null, 'email' => null],
                'preferences' => ['preference_revision' => $profile->preferenceRevision],
            };
        } else {
            $identity = $this->api(AuthIdentities::class)->identity($id) ?? throw new OperationRejected('identity_missing', 'runa.auth', 'identity.read', 404);
            $version = $identity->version;
            $values = match (static::KEY) {
                'invitations' => [],
                'eligibility' => $this->eligibility($id),
                'sessions' => ['sessions' => json_encode($this->api(AuthSessions::class)->listFor($id, $input->cursor), JSON_THROW_ON_ERROR), 'session_id' => null],
                'security' => [],
                'trust' => ['devices' => json_encode($this->api(AuthSecurity::class)->trustedDevices($id, $input->cursor), JSON_THROW_ON_ERROR), 'device_id' => null],
                'exceptions' => ['exceptions' => json_encode($this->api(AuthPolicies::class)->exceptions($id), JSON_THROW_ON_ERROR), 'key' => 'session_idle_minutes', 'value' => null,
                    'reason' => null, 'expires_at' => null, 'exception_version' => 0, 'policy_revision' => $this->settings->read('runa.auth')['revision']],
                default => [],
            };
        }

        return new ManagementRecord($id, (string) $version, $values);
    }

    public function page(ManagementRead $input): ManagementPageResult
    {
        throw new OperationRejected('admin_operation_unavailable', 'runa.auth', 'administration.read', 404);
    }

    public function preview(ManagementWrite $input): ManagementReview
    {
        throw new OperationRejected('admin_operation_unavailable', 'runa.auth', 'administration.preview', 404);
    }

    public function execute(ManagementWrite $input): ManagementOutcome
    {
        try {
            $this->validateInput($input);
            $id = $input->publicId;
            $v = $input->values;
            foreach (['name', 'first_name', 'surname', 'login'] as $key) {
                if (($v[$key] ?? null) === '') {
                    $v[$key] = null;
                }
            }
            $version = (int) $input->revision;
            $result = match (static::KEY.'.'.$input->command) {
                'creation.create' => $this->api(AuthLifecycle::class)->create(new NewIdentity($v['email'], $v['name'], $v['first_name'], $v['surname'], $v['login'], $v['active'], $v['approved'])),
                'lifecycle.change' => $this->api(AuthLifecycle::class)->change($id, $version, new LifecycleChange($v['operation'], in_array($v['operation'], ['schedule_activation', 'schedule_deactivation', 'set_expiry', 'set_attempt_lock'], true) ? $v['at'] : null, in_array($v['operation'], ['set_security_lock', 'require_password_change'], true) ? $v['enabled'] : null)),
                'invitations.invite' => $this->api(AuthInvitations::class)->invite($id, $version),
                'invitations.invalidate' => $this->api(AuthInvitations::class)->invalidate($id, $version),
                'primary_email.change' => $this->api(AuthProfiles::class, '2.0.0')->changeEmail($id, $version, $v['email']),
                'primary_email.replace' => $this->api(AuthProfiles::class, '2.0.0')->replaceVerifiedEmail($id, $version, $v['replacement_email']),
                'primary_email.require_verification' => $this->api(AuthProfiles::class, '2.0.0')->requireVerification($id, $version),
                'secondary.add' => $this->api(AuthProfiles::class, '2.0.0')->addSecondary($id, $version, $v['email']),
                'secondary.change' => $this->api(AuthProfiles::class, '2.0.0')->changeSecondary($id, $version, $v['address_id'], $v['email']),
                'secondary.remove' => $this->api(AuthProfiles::class, '2.0.0')->removeSecondary($id, $version, $v['address_id']),
                'sessions.revoke' => $this->api(AuthSessions::class)->revoke($id, $v['session_id']),
                'sessions.revoke_all' => $this->api(AuthSessions::class)->revokeAll($id),
                'security.password_reset' => $this->api(AuthSecurity::class)->requestPasswordReset($id, $version),
                'security.factor_reset' => $this->api(AuthSecurity::class)->resetFactors($id, $version),
                'trust.revoke_device' => $this->api(AuthSecurity::class)->revokeDevice($id, $v['device_id']),
                'preferences.reset' => $this->api(AuthPreferences::class)->resetAccount($id, $v['preference_revision']),
                'exceptions.set' => $this->api(AuthPolicies::class)->exception($id, $v['key'], $v['value'], $v['reason'], $v['expires_at'], $v['exception_version'], $v['policy_revision']),
                default => throw new \InvalidArgumentException('Unknown administration command.'),
            };
            if ($result === false) {
                throw new OperationRejected('identity_conflict', 'runa.auth', 'administration.command', 409);
            }

            // Independent protocols may revoke current entry or change availability. Never imply atomic Save.
            try {
                $record = $this->read(new ManagementRead($id));
            } catch (OperationRejected) {
                $record = null;
            }

            return new ManagementOutcome('committed', $record);
        } catch (IdentityConflict) {
            throw new OperationRejected('identity_conflict', 'runa.auth', 'administration.command', 409);
        } catch (\InvalidArgumentException|\TypeError) {
            throw new OperationRejected('admin_input_invalid', 'runa.auth', 'administration.command', 422);
        }
    }

    /** @return array{blockers:string,restrictions:string} */
    private function eligibility(string $id): array
    {
        $state = $this->api(AuthIdentities::class)->eligibility($id) ?? throw new OperationRejected('identity_missing', 'runa.auth', 'eligibility.read', 404);

        return ['blockers' => implode(', ', $state->blockers), 'restrictions' => implode(', ', $state->restrictions)];
    }

    private function validateInput(ManagementWrite $input): void
    {
        $spec = AdministrationPages::commands()[static::KEY][$input->command] ?? throw new \InvalidArgumentException('Unknown command.');
        $keys = array_keys($input->values);
        sort($keys);
        $expected = $spec['inputs'];
        sort($expected);
        if ($keys !== $expected || preg_match('/\A[0-9]{1,9}\z/D', $input->revision) !== 1
            || (static::KEY === 'creation' ? $input->publicId !== null : ($input->publicId === null || (int) $input->revision < 1))
            || ($spec['destructive'] && ! $input->confirmed)) {
            throw new \InvalidArgumentException('Invalid administration input.');
        }
        $section = array_values(array_filter((new AdministrationPages)->userSections(), fn ($section) => $section->key === static::KEY))[0];
        $fields = [];
        foreach ($section->fields as $access) {
            $fields[$access->field->key] = $access->field;
        }
        foreach ($input->values as $key => $value) {
            $field = $fields[$key];
            if ($value === null && in_array($key, ['name', 'first_name', 'surname', 'login', 'at', 'value'], true)) {
                continue;
            }
            $valid = match ($field->type) {
                'text', 'datetime' => is_string($value) && ($field->maxLength === null || mb_strlen($value) <= $field->maxLength),
                'boolean' => is_bool($value),
                'integer' => is_int($value) && $value >= 0,
                'dictionary' => is_string($value) && in_array($value, $field->choices, true),
                'uuid' => is_string($value),
                default => false,
            };
            if (! $valid) {
                throw new \InvalidArgumentException('Invalid administration field type.');
            }
            if ($field->type === 'uuid') {
                new TrustedPrincipal($value);
            }
        }
    }

    /** @template T of object
     * @param class-string<T> $contract
     * @return T */
    private function api(string $contract, string $version = '1.0.0'): object
    {
        return $this->apis->resolve('runa.auth', $contract, $version);
    }
}
