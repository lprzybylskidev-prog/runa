<?php

namespace Runa\Modules\Auth\Models;

use Carbon\CarbonImmutable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Carbon;
use Runa\Modules\Auth\Actions\EntryTokens;
use Runa\Modules\Auth\Contracts\Identity;
use Runa\Support\Modules\Data\ModuleModel;

/**
 * Auth-owned persistence. Mutations belong to audited lifecycle/credential Actions.
 *
 * @property string $email
 * @property string|null $email_claim
 * @property string|null $login_claim
 * @property string|null $password
 * @property string|null $remember_token
 * @property bool $active
 * @property bool $approved
 * @property bool $security_locked
 * @property bool $password_change_required
 * @property int $version
 * @property int $security_version
 * @property string|null $name
 * @property string|null $first_name
 * @property string|null $surname
 * @property string|null $login
 * @property-read CarbonImmutable|null $password_changed_at
 * @property-write \DateTimeInterface|string|null $password_changed_at
 * @property string|null $totp_secret
 * @property string|null $totp_pending
 * @property-read CarbonImmutable|null $totp_pending_until
 * @property-write \DateTimeInterface|string|null $totp_pending_until
 * @property int|null $totp_counter
 * @property bool $email_factor
 * @property list<string>|null $recovery_hashes
 * @property string|null $avatar_path
 * @property string|null $avatar_type
 * @property bool $verification_required
 * @property bool $verification_forced
 * @property int $preference_revision
 * @property bool $preference_legacy_claimed
 * @property bool $operator_recovery_pending
 * @property-read CarbonImmutable|null $activate_at
 * @property-write \DateTimeInterface|string|null $activate_at
 * @property-read CarbonImmutable|null $deactivate_at
 * @property-write \DateTimeInterface|string|null $deactivate_at
 * @property-read CarbonImmutable|null $expires_at
 * @property-write \DateTimeInterface|string|null $expires_at
 * @property-read CarbonImmutable|null $attempt_locked_until
 * @property-write \DateTimeInterface|string|null $attempt_locked_until
 * @property-read CarbonImmutable|null $email_verified_at
 * @property-write \DateTimeInterface|string|null $email_verified_at
 * @property int $id
 * @property string $public_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Carbon|null $deleted_at
 * @property-write \DateTimeInterface|string|null $deleted_at
 */
final class User extends ModuleModel implements AuthenticatableContract, MustVerifyEmail
{
    use Authenticatable;

    protected $table = 'runa_auth.users';

    protected $guarded = ['*'];

    // Never serialize private account state through Eloquent's default projection.
    protected $visible = ['public_id'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'operator_recovery_pending' => 'boolean', 'preference_revision' => 'integer', 'preference_legacy_claimed' => 'boolean', 'verification_forced' => 'boolean', 'verification_required' => 'boolean', 'active' => 'boolean', 'approved' => 'boolean', 'security_locked' => 'boolean',
            'password_change_required' => 'boolean', 'version' => 'integer', 'security_version' => 'integer',
            'activate_at' => 'immutable_datetime', 'deactivate_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime', 'attempt_locked_until' => 'immutable_datetime',
            'email_verified_at' => 'immutable_datetime', 'password_changed_at' => 'immutable_datetime',
            'totp_pending_until' => 'immutable_datetime', 'totp_counter' => 'integer',
            'email_factor' => 'boolean', 'recovery_hashes' => 'array',
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
