<?php

namespace Runa\Modules\Auth\Management;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
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
use Runa\ModuleFramework\Management\InputValues as Values;
use Runa\Modules\Auth\Contracts\AuthLifecycle;
use Runa\Modules\Auth\Contracts\IdentityConflict;
use Runa\Modules\Auth\Contracts\IdentitySetup;
use Runa\Modules\Auth\Contracts\NewIdentity;
use Runa\Modules\Auth\Support\EntryPassword;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Settings\ModuleSettings;

final readonly class CreationSection implements ManagementResourceReader, ManagementWriter
{
    public const INPUTS = ['email', 'name', 'first_name', 'surname', 'login', 'active', 'credential_mode',
        'password', 'password_confirmation', 'email_verified', 'activate_at', 'deactivate_at',
        'security_locked', 'attempt_locked_until', 'password_change_required'];

    public function __construct(private ModuleApis $apis, private ModuleSettings $settings) {}

    public function read(ManagementRead $input): ManagementRecord
    {
        if ($input->publicId !== null || $input->cursor !== null) {
            throw new OperationRejected('admin_input_invalid', 'runa.auth', 'identity.create', 422);
        }
        $policy = [];
        foreach (['min_length', 'mixed_case', 'numbers', 'symbols'] as $key) {
            $policy[$key] = $this->settings->value('runa.auth', 'password_'.$key);
        }
        $policy['max_bytes'] = 72;

        return new ManagementRecord(null, '0', [
            'email' => null, 'name' => null, 'first_name' => null, 'surname' => null, 'login' => null,
            'active' => false, 'credential_mode' => 'none', 'password' => ['set' => false],
            'password_confirmation' => ['set' => false], 'email_verified' => false,
            'activate_at' => null, 'deactivate_at' => null, 'security_locked' => false,
            'attempt_locked_until' => null, 'password_change_required' => false,
            'identifier_mode' => $this->settings->value('runa.auth', 'identifier_mode'),
            'password_policy' => json_encode($policy, JSON_THROW_ON_ERROR),
            'invitation_allowed' => true,
            'email_verification_allowed' => $this->settings->boolean('runa.auth', 'admin_email_verification_bypass'),
        ]);
    }

    public function page(ManagementRead $input): ManagementPageResult
    {
        throw new OperationRejected('admin_operation_unavailable', 'runa.auth', 'identity.create', 404);
    }

    public function preview(ManagementWrite $input): ManagementReview
    {
        if ($input->command !== 'create' || $input->publicId !== null || $input->revision !== '0'
            || ! is_bool($input->values['email_verified'] ?? null)) {
            throw new OperationRejected('admin_input_invalid', 'runa.auth', 'identity.create', 422);
        }
        $verified = $input->values['email_verified'];
        if ($verified) {
            app(OperationGuard::class)->enter('runa.auth', new ApiOperation('email.admin_bypass', 'runa.auth.emails.verify_bypass'));
            if (! $this->settings->boolean('runa.auth', 'admin_email_verification_bypass')) {
                throw new OperationRejected('email_bypass_disabled', 'runa.auth', 'identity.create', 403);
            }
        }

        // This review selects the owning proof, never fingerprints or echoes secrets.
        return new ManagementReview(hash('sha256', $verified ? 'verified' : 'unverified'), [], 'password', true);
    }

    public function execute(#[\SensitiveParameter] ManagementWrite $input): ManagementOutcome
    {
        if ($input->command !== 'create' || $input->publicId !== null || $input->revision !== '0'
            || array_diff(array_keys($input->values), self::INPUTS) !== []) {
            throw new OperationRejected('admin_input_invalid', 'runa.auth', 'identity.create', 422);
        }
        $v = $input->values;
        foreach (['name', 'first_name', 'surname', 'login', 'activate_at', 'deactivate_at', 'attempt_locked_until'] as $key) {
            if (($v[$key] ?? null) === '') {
                $v[$key] = null;
            }
        }
        Validator::make($v, [
            'email' => ['required', 'string', 'email', 'max:254'],
            'name' => ['nullable', 'string', 'max:160'], 'first_name' => ['nullable', 'string', 'max:160'],
            'surname' => ['nullable', 'string', 'max:160'], 'login' => ['nullable', 'string', 'max:64'],
            'credential_mode' => ['required', 'in:none,invitation,password'],
            'active' => ['required', 'boolean:strict'], 'email_verified' => ['required', 'boolean:strict'],
            'security_locked' => ['required', 'boolean:strict'], 'password_change_required' => ['required', 'boolean:strict'],
            'activate_at' => ['nullable', 'string'], 'deactivate_at' => ['nullable', 'string'], 'attempt_locked_until' => ['nullable', 'string'],
            'password' => ($v['credential_mode'] ?? null) === 'password' ? EntryPassword::rules() : ['prohibited'],
            'password_confirmation' => ($v['credential_mode'] ?? null) === 'password' ? ['required', 'string'] : ['prohibited'],
        ])->validate();
        try {
            $setup = new IdentitySetup(Values::string($v['credential_mode']), Values::nullableString($v['password'] ?? null), Values::boolean($v['email_verified']),
                Moment::seconds($v['activate_at'] ?? null), Moment::seconds($v['deactivate_at'] ?? null),
                Values::boolean($v['security_locked']), Moment::seconds($v['attempt_locked_until'] ?? null), Values::boolean($v['password_change_required']));
            $this->apis->resolve('runa.auth', AuthLifecycle::class, '2.1.0')->create(
                new NewIdentity(Values::string($v['email']), Values::nullableString($v['name'] ?? null), Values::nullableString($v['first_name'] ?? null), Values::nullableString($v['surname'] ?? null), Values::nullableString($v['login'] ?? null), Values::boolean($v['active'])), $setup);

            return new ManagementOutcome('committed', $this->read(new ManagementRead));
        } catch (IdentityConflict $error) {
            throw new OperationRejected($error->getMessage(), 'runa.auth', 'identity.create', 409);
        } catch (\InvalidArgumentException|\TypeError) {
            throw ValidationException::withMessages(['name' => ['Invalid creation options.']]);
        }
    }
}
