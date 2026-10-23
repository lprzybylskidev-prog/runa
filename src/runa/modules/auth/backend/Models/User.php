<?php

namespace Runa\Modules\Auth\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Runa\Modules\Auth\Actions\EntryTokens;
use Runa\Modules\Auth\Contracts\Identity;
use Runa\Support\Modules\Data\ModuleModel;

/** Auth-owned persistence. Mutations belong to audited lifecycle/credential Actions. */
final class User extends ModuleModel implements AuthenticatableContract, MustVerifyEmail
{
    use Authenticatable;

    protected $table = 'runa_auth.users';

    protected $guarded = ['*'];

    // Never serialize private account state through Eloquent's default projection.
    protected $visible = ['public_id'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean', 'approved' => 'boolean', 'security_locked' => 'boolean',
            'password_change_required' => 'boolean', 'version' => 'integer', 'security_version' => 'integer',
            'activate_at' => 'immutable_datetime', 'deactivate_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime', 'attempt_locked_until' => 'immutable_datetime',
            'email_verified_at' => 'immutable_datetime',
        ];
    }

    public static function moduleId(): string
    {
        return 'runa.auth';
    }

    public function getAuthIdentifierName(): string
    {
        return 'public_id';
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function getEmailForVerification(): string
    {
        return $this->email;
    }

    public function sendEmailVerificationNotification(): void
    {
        app(EntryTokens::class)->resendVerification($this->public_id);
    }

    public function markEmailAsUnverified(): bool
    {
        throw new \LogicException('Reverification requires an authorized owning Action.');
    }

    public function markEmailAsVerified(): bool
    {
        throw new \LogicException('Verification requires the owning single-use token Action.');
    }

    public function identity(): Identity
    {
        return new Identity($this->public_id, $this->name ?? $this->first_name.' '.$this->surname, $this->trashed(), $this->version);
    }

    public function save(array $options = [])
    {
        if ($this->exists && ($this->isDirty('public_id') || $this->isDirty('id'))) {
            throw new \LogicException('Auth identity is immutable.');
        }
        if ($this->getConnection() !== app('db')->connection() || $this->getConnection()->transactionLevel() < 1) {
            throw new \LogicException('Auth writes require the default transaction and an owning Action.');
        }

        return parent::save($options);
    }

    public function delete()
    {
        throw new \LogicException('Use Auth lifecycle Actions for deletion and revocation.');
    }

    public function restore()
    {
        throw new \LogicException('Use Auth lifecycle Actions for conflict-safe restoration.');
    }

    public function forceDelete()
    {
        throw new \LogicException('Physical account deletion belongs to future data management.');
    }
}
