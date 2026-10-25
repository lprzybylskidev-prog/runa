<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Runa\Modules\Auth\Contracts\IdentityConflict;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Modules\Auth\Support\Identifiers;
use Runa\Modules\Auth\Support\SecurityContext;
use Runa\Support\Modules\Api\OperationRejected;

final readonly class EmailAddresses
{
    public function __construct(private EntryTransaction $transactions, private SecurityContext $context, private Profiles $profiles,
        private ProfileMail $mail, private SessionStore $sessions, private LookupIdentity $identities) {}

    public function ownPrimary(Request $request): array
    {
        return $this->context->run($request, function (Connection $connection, User $user, object $session) use ($request): ?array {
            if (! $this->profiles->value('own_email_editable')) {
                $this->refuse('email_edit_disabled');
            }
            if (! Hash::check($request->string('current_password')->toString(), $user->password ?? '')) {
                return null;
            }
            $this->profiles->version($user, $request->integer('version'));
            $this->startLocked($connection, $user, $request->string('email')->toString(), false, $session->session_hash);

            return ['pending' => true];
        }, unrestricted: true);
    }

    public function startLocked(Connection $connection, User $user, string $email, bool $admin, ?string $sessionHash = null): void
    {
        $email = Identifiers::email($email);
        $this->available($connection, $user, $email, true);
        if ($email === $user->email) {
            $this->refuse('email_unchanged');
        }
        $connection->table('runa_auth.email_changes')->where('user_id', $user->id)->whereNull('completed_at')->whereNull('cancelled_at')->update(['cancelled_at' => now()]);
        $oldRequired = ! $admin && $this->profiles->value('email_change_old_confirmation');
        $id = $connection->table('runa_auth.email_changes')->insertGetId([
            'public_id' => (string) Str::uuid7(), 'user_id' => $user->id, 'old_email' => $user->email, 'new_email' => $email,
            'security_version' => $user->security_version, 'session_hash' => $sessionHash,
            'session_policy' => $admin ? 'revoke_all' : $this->profiles->value('email_change_session_policy'),
            'administrative' => $admin, 'old_required' => $oldRequired, 'expires_at' => now()->addMinutes($this->profiles->value('email_change_minutes')),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->mail->issueLocked($connection, $user, 'primary_new', $email, $id);
        if ($oldRequired) {
            $this->mail->issueLocked($connection, $user, 'primary_old', $user->email, $id);
        }
        $user->version++;
        $user->save();
        $this->transactions->record($admin ? 'email.admin_requested' : 'email.requested', $user);
    }

    public function complete(#[\SensitiveParameter] string $secret): void
    {
        $this->transactions->run(function (Connection $connection) use ($secret): void {
            $this->transactions->lockAuthority($connection);
            $candidate = $connection->table('runa_auth.profile_mail')->where('token_hash', hash('sha256', $secret))->first();
            $user = $candidate === null ? null : User::where('id', $candidate->user_id)->lockForUpdate()->first();
            $token = $candidate === null ? null : $connection->table('runa_auth.profile_mail')->where('id', $candidate->id)->lockForUpdate()->first();
            if (! $this->mail->validLocked($connection, $user, $token) || $token->purpose === 'notice') {
                $this->refuse('email_token_invalid');
            }
            $connection->table('runa_auth.profile_mail')->where('id', $token->id)->update(['consumed_at' => now(), 'delivery_secret' => null]);
            if ($token->purpose === 'secondary') {
                $row = $connection->table('runa_auth.secondary_emails')->where('id', $token->secondary_id)->first();
                $this->available($connection, $user, $row->email, false, $row->id);
                $connection->table('runa_auth.secondary_emails')->where('id', $row->id)->update(['verified_at' => now(), 'updated_at' => now()]);
                $user->version++;
                $user->save();
                $this->transactions->record('secondary.verified', $user, ['email' => ['old' => $row->email, 'new' => $row->email], 'verified' => ['old' => false, 'new' => true]]);

                return;
            }
            $change = $connection->table('runa_auth.email_changes')->where('id', $token->change_id)->first();
            $field = $token->purpose === 'primary_new' ? 'new_confirmed' : 'old_confirmed';
            $connection->table('runa_auth.email_changes')->where('id', $change->id)->update([$field => true, 'updated_at' => now()]);
            $change->$field = true;
            if ($change->new_confirmed && (! $change->old_required || $change->old_confirmed)) {
                $this->replaceLocked($connection, $user, $change->new_email, $change->session_policy, $change->session_hash, $change->administrative);
                $connection->table('runa_auth.email_changes')->where('id', $change->id)->update(['completed_at' => now(), 'cancelled_at' => null]);
            }
        });
    }

    public function replaceLocked(Connection $connection, User $user, string $email, string $policy, ?string $sessionHash, bool $admin): void
    {
        $email = Identifiers::email($email);
        $this->available($connection, $user, $email, true);
        if ($email === $user->email) {
            $this->refuse('email_unchanged');
        }
        $old = $user->email;
        $user->email = $email;
        $user->email_claim = $email;
        $user->email_verified_at = CarbonImmutable::now('UTC');
        $user->verification_forced = false;
        $user->remember_token = null;
        $user->version++;
        if ($admin) {
            $user->security_version++;
            $this->sessions->revokeUser($connection, $user);
        } else {
            $query = $connection->table('runa_auth.sessions')->where('user_id', $user->id)->whereNull('revoked_at');
            if ($policy === 'revoke_others') {
                $query->where('session_hash', '<>', $sessionHash);
            }
            if ($policy !== 'keep_all') {
                $query->update(['revoked_at' => now()]);
            }
            $connection->table('runa_auth.trusted_devices')->where('user_id', $user->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
        }
        $user->save();
        $connection->table('runa_auth.entry_tokens')->where('user_id', $user->id)->whereNull('consumed_at')->whereNull('invalidated_at')->update(['invalidated_at' => now(), 'delivery_secret' => null]);
        $connection->table('runa_auth.step_up_proofs')->where('user_id', $user->id)->delete();
        $connection->table('runa_auth.email_changes')->where('user_id', $user->id)->whereNull('completed_at')->whereNull('cancelled_at')->update(['cancelled_at' => now()]);
        $this->mail->issueLocked($connection, $user, 'notice', $old);
        $this->transactions->record($admin ? 'email.admin_changed' : 'email.changed', $user, ['email' => ['old' => $old, 'new' => $email]]);
    }

    public function ownSecondary(Request $request, string $operation): array
    {
        return $this->context->run($request, function (Connection $connection, User $user) use ($request, $operation): array {
            if (! $this->profiles->value('secondary_self_'.$operation)) {
                $this->refuse('secondary_edit_disabled');
            }
            $this->profiles->version($user, $request->integer('version'));
            $id = $this->secondaryLocked($connection, $user, $operation, $request->input('email'), $request->input('address_id'));

            return ['updated' => true, 'public_id' => $id];
        }, unrestricted: true);
    }

    public function secondaryLocked(Connection $connection, User $user, string $operation, ?string $email, ?string $addressId): ?string
    {
        if (! $this->profiles->value('secondary_emails_enabled')) {
            $this->refuse('secondary_disabled');
        }
        $query = $connection->table('runa_auth.secondary_emails')->where('user_id', $user->id)->whereNull('deleted_at');
        $row = $operation === 'add' ? null : (clone $query)->where('public_id', $addressId)->first();
        if ($operation !== 'add' && $row === null) {
            $this->refuse('secondary_missing');
        }
        if ($operation === 'remove') {
            $connection->table('runa_auth.secondary_emails')->where('id', $row->id)->update(['deleted_at' => now(), 'updated_at' => now()]);
        } else {
            $email = Identifiers::email($email ?? '');
            $this->available($connection, $user, $email, false, $row?->id);
            if ($row !== null && $row->email === $email && $row->verified_at !== null) {
                $this->refuse('email_unchanged');
            }
            if ($row === null && $query->count() >= $this->profiles->value('secondary_email_limit')) {
                $this->refuse('secondary_limit');
            }
            $publicId = $row?->public_id ?? (string) Str::uuid7();
            $values = ['email' => $email, 'verified_at' => null, 'updated_at' => now()];
            if ($row === null) {
                $id = $connection->table('runa_auth.secondary_emails')->insertGetId($values + ['user_id' => $user->id, 'public_id' => $publicId, 'created_at' => now()]);
            } else {
                $id = $row->id;
                $connection->table('runa_auth.secondary_emails')->where('id', $id)->update($values);
                $connection->table('runa_auth.profile_mail')->where('secondary_id', $id)->whereNull('consumed_at')->update(['consumed_at' => now(), 'delivery_secret' => null]);
            }
            $this->mail->issueLocked($connection, $user, 'secondary', $email, secondaryId: $id);
        }
        $user->version++;
        $user->save();
        $this->transactions->record('secondary.'.match ($operation) {
            'add' => 'added', 'change' => 'changed', 'remove' => 'removed'
        }, $user, ['email' => ['old' => $row?->email, 'new' => $operation === 'remove' ? null : $email]]);

        return $operation === 'remove' ? null : $publicId;
    }

    /** All address ownership arbitration holds registry and authority locks, plus target user lock. */
    public function available(Connection $connection, User $user, string $email, bool $primary, ?int $ignoreSecondary = null): void
    {
        $sharing = $this->profiles->value('secondary_email_sharing');
        $primaryQuery = $connection->table('runa_auth.users')->where('email_claim', $email);
        if (($primary || ! $sharing) && (clone $primaryQuery)->where('id', '<>', $user->id)->exists()) {
            throw new IdentityConflict('identifier_conflict');
        }
        if (! $primary && $email === $user->email) {
            throw new IdentityConflict('identifier_conflict');
        }
        $secondary = $connection->table('runa_auth.secondary_emails')->whereNull('deleted_at')->where('email', $email);
        if ($ignoreSecondary !== null) {
            $secondary->where('id', '<>', $ignoreSecondary);
        }
        if ($sharing) {
            $secondary->where('user_id', $user->id);
        }
        if ($secondary->exists()) {
            throw new IdentityConflict('identifier_conflict');
        }
    }

    private function refuse(string $code): never
    {
        throw new OperationRejected($code, 'runa.auth', 'email', 422);
    }
}
