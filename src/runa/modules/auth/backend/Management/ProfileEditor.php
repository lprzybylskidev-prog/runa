<?php

namespace Runa\Modules\Auth\Management;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ManagementOutcome;
use Runa\Contracts\Modules\ManagementPageResult;
use Runa\Contracts\Modules\ManagementParticipant;
use Runa\Contracts\Modules\ManagementRead;
use Runa\Contracts\Modules\ManagementRecord;
use Runa\Contracts\Modules\ManagementResourceReader;
use Runa\Contracts\Modules\ManagementReview;
use Runa\Contracts\Modules\ManagementWrite;
use Runa\Contracts\Modules\ManagementWriter;
use Runa\Contracts\Modules\TransactionalMutation;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\Modules\Auth\Actions\ProfileMutation;
use Runa\Modules\Auth\Actions\Profiles;
use Runa\Modules\Auth\Contracts\IdentityConflict;
use Runa\Modules\Auth\Contracts\ProfileChange;
use Runa\Modules\Auth\Models\User;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Data\ModuleTransactions;
use Runa\Support\Modules\Settings\ModuleSettings;

/** Auth-owned declaration adapter, not a foreign persistence/factory entrypoint. */
final readonly class ProfileEditor implements ManagementParticipant, ManagementResourceReader, ManagementWriter
{
    public function __construct(private OperationGuard $guard, private Profiles $profiles, private ModuleTransactions $transactions, private PrincipalScope $principals, private ModuleSettings $settings) {}

    public function read(ManagementRead $input): ManagementRecord
    {
        $this->guard->enter('runa.auth', new ApiOperation('identity.read', 'runa.auth.identities.read'));
        $publicId = $input->publicId ?? $this->principals->current()?->publicId;
        $user = $publicId === null ? null : User::where('public_id', $publicId)->first();
        if ($user === null) {
            throw new OperationRejected('identity_missing', 'runa.auth', 'identity.read', 404);
        }

        return new ManagementRecord($user->public_id, (string) $user->version,
            ['name' => $user->name, 'first_name' => $user->first_name, 'surname' => $user->surname, 'login' => $user->login, 'identifier_mode' => $this->settings->value('runa.auth', 'identifier_mode')]);
    }

    public function page(ManagementRead $input): ManagementPageResult
    {
        throw new OperationRejected('admin_operation_unavailable', 'runa.auth', 'identity.read', 404);
    }

    public function preview(ManagementWrite $input): ManagementReview
    {
        $this->guard->enter('runa.auth', new ApiOperation('profile.edit', 'runa.auth.profiles.manage'));
        $change = $this->change($input);
        $before = $this->read(new ManagementRead($input->publicId));
        if (! hash_equals($before->revision, $input->revision)) {
            throw new OperationRejected('identity_conflict', 'runa.auth', 'profile.edit', 409);
        }
        $changes = [];
        $values = ['name' => $change->name, 'first_name' => $change->firstName, 'surname' => $change->surname, 'login' => $change->login];
        foreach ($values as $key => $value) {
            if ($before->values[$key] !== $value) {
                $changes[$key] = ['old' => $before->values[$key], 'new' => $value];
            }
        }
        ksort($values);

        return new ManagementReview(hash('sha256', serialize([$before, $values])), $changes, 'none', false);
    }

    public function prepare(ManagementWrite $input): TransactionalMutation
    {
        $this->guard->enter('runa.auth', new ApiOperation('profile.edit', 'runa.auth.profiles.manage'));
        if ($input->publicId === null || preg_match('/\A[1-9][0-9]{0,8}\z/D', $input->revision) !== 1) {
            throw new OperationRejected('admin_input_invalid', 'runa.auth', 'profile.edit', 422);
        }

        return new ProfileMutation($this->profiles, $input->publicId, (int) $input->revision, $this->change($input));
    }

    public function execute(ManagementWrite $input): ManagementOutcome
    {
        $mutation = $this->prepare($input);
        try {
            $result = $this->transactions->run([$mutation]);
        } catch (IdentityConflict) {
            throw new OperationRejected('identity_conflict', 'runa.auth', 'profile.edit', 409);
        }

        return $this->project($result['runa.auth.'.$mutation->definition()->key]);
    }

    public function project(array $result): ManagementOutcome
    {
        return new ManagementOutcome('committed', $this->read(new ManagementRead($result['public_id'])));
    }

    private function change(ManagementWrite $input): ProfileChange
    {
        if ($input->command !== 'save' || count($input->values) !== 4 || array_diff(array_keys($input->values), ['name', 'first_name', 'surname', 'login']) !== []) {
            throw new OperationRejected('admin_input_invalid', 'runa.auth', 'profile.edit', 422);
        }
        try {
            $values = array_map(fn ($value) => $value === '' ? null : $value, $input->values);

            return new ProfileChange($values['name'], $values['first_name'], $values['surname'], $values['login']);
        } catch (\InvalidArgumentException|\TypeError) {
            throw new OperationRejected('admin_input_invalid', 'runa.auth', 'profile.edit', 422);
        }
    }
}
