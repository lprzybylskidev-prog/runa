<?php

namespace Runa\Modules\Auth\Actions;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\MutationAccess;
use Runa\Contracts\Modules\MutationDefinition;
use Runa\Contracts\Modules\TransactionalMutation;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Auth\Contracts\Identity;
use Runa\Modules\Auth\Contracts\IdentityConflict;
use Runa\Modules\Auth\Contracts\ProfileChange;
use Runa\Modules\Auth\Models\User;

/** Fresh, source-owned mutation; no public model or prepared-plan transport. */
final class ProfileMutation implements TransactionalMutation
{
    private ?User $user = null;

    private bool $locked = false;

    private bool $validated = false;

    private bool $applied = false;

    public function __construct(private readonly Profiles $profiles, private readonly string $publicId,
        private readonly int $version, private readonly ProfileChange $change)
    {
        new TrustedPrincipal($publicId);
    }

    public function definition(): MutationDefinition
    {
        return new MutationDefinition('runa.auth', 'profile.edit.u'.str_replace('-', '', $this->publicId), [
            new MutationAccess('runa.auth', new ApiOperation('profile.edit', 'runa.auth.profiles.manage')),
        ], [$this->publicId], ['runa.auth.profile:'.$this->publicId]);
    }

    public function preflight(): void {}

    public function lock(): void
    {
        if ($this->locked) {
            throw new \LogicException('Profile mutation cannot be reused.');
        }
        $this->locked = true;
        $this->user = User::where('public_id', $this->publicId)->lockForUpdate()->first() ?? throw new IdentityConflict('identity_missing');
    }

    public function validate(): void
    {
        $this->profiles->validateEditLocked($this->user ?? throw new \LogicException('Profile is not locked.'), $this->version, $this->change, false);
        $this->validated = true;
    }

    public function apply(): void
    {
        if (! $this->validated || $this->applied || $this->user === null) {
            throw new \LogicException('Profile requires one validated apply.');
        }
        $this->profiles->applyEditLocked($this->user, $this->change, false);
        $this->applied = true;
    }

    public function identity(): Identity
    {
        if (! $this->applied || $this->user === null) {
            throw new \LogicException('Profile mutation has no committed projection.');
        }

        return $this->user->identity();
    }

    public function result(): array
    {
        $identity = $this->identity();

        return ['public_id' => $identity->publicId, 'version' => $identity->version];
    }

    public function refused(string $reason): void
    {
        $this->user = null;
        $this->validated = $this->applied = false;
    }
}
