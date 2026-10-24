<?php

namespace Runa\Modules\Auth\Actions;

use Illuminate\Database\Connection;
use Runa\Contracts\Modules\AuthorityGuard;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\EntryTransaction;

/** Current predicates remain authoritative before this bounded materialization job runs. */
final readonly class ScheduledLifecycle
{
    public function __construct(private EntryTransaction $transactions, private AuthorityGuard $authority, private SessionStore $sessions) {}

    public function run(): void
    {
        $ids = User::where(fn ($q) => $q->where('activate_at', '<=', now())->orWhere('deactivate_at', '<=', now()))
            ->orderBy('public_id')->limit(100)->pluck('public_id');
        foreach ($ids as $id) {
            $this->transactions->run(function (Connection $connection) use ($id): void {
                $this->transactions->lockAuthority($connection);
                $user = User::where('public_id', $id)->lockForUpdate()->first();
                if ($user === null) {
                    return;
                }
                $deactivate = $user->deactivate_at !== null && $user->deactivate_at->lte(now());
                $activate = $user->activate_at !== null && $user->activate_at->lte(now());
                if (! $deactivate && ! $activate) {
                    return;
                }
                $this->authority->assertMayRemove($connection, [$id]);
                $user->active = ! $deactivate;
                $user->activate_at = null;
                if ($deactivate) {
                    $user->deactivate_at = null;
                }
                $user->security_version++;
                $user->version++;
                $user->remember_token = null;
                $user->save();
                $this->sessions->revokeUser($connection, $user);
                $this->transactions->record($deactivate ? 'identity.scheduled_deactivated' : 'identity.scheduled_activated', $user);
            });
        }
    }
}
