<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\ModuleFramework\Api\OperationScope;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\Modules\Auth\Contracts\AuthPreferences;
use Runa\Modules\Auth\Contracts\IdentityConflict;
use Runa\Modules\Auth\Contracts\PreferenceScope;
use Runa\Modules\Auth\Contracts\PreferenceValue;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\AccountPreferenceSnapshot;
use Runa\Modules\Auth\Queries\LookupIdentity;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Modules\Auth\Support\PreferenceDeclarations;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\ModuleIdentity;
use Runa\Support\Modules\Settings\ModuleSettings;

/** @phpstan-import-type PreferenceRead from AccountPreferenceSnapshot */
final readonly class AccountPreferences implements AuthPreferences
{
    public function __construct(private EntryTransaction $transactions, private PreferenceDeclarations $declarations,
        private PrincipalScope $principals, private AccountPreferenceSnapshot $snapshots,
        private OperationAuthorizer $authorizer, private OperationScope $scope) {}

    public function account(string $publicId): array
    {
        new TrustedPrincipal($publicId);

        return $this->target($publicId, function (Connection $connection, User $user): array {
            $locale = $this->readLocked($connection, $user, new PreferenceScope('runa', 'runa.auth', 'global', 'locale'));
            $theme = $this->readLocked($connection, $user, new PreferenceScope('runa', 'runa.auth', 'global', 'theme'));
            assert(is_string($locale['value']) && is_string($theme['value']));
            $canReadAddresses = $this->allowsAddresses();
            $recipients = $canReadAddresses ? [['value' => 'primary', 'label' => $user->email]] : [];
            if ($canReadAddresses && app(ModuleSettings::class)->value('runa.auth', 'secondary_emails_enabled')) {
                /** @var \stdClass&object{public_id:string,email:string} $row */
                foreach ($connection->table('runa_auth.secondary_emails')->where('user_id', $user->id)->whereNull('deleted_at')->whereNotNull('verified_at')->get(['public_id', 'email']) as $row) {
                    $recipients[] = ['value' => $row->public_id, 'label' => $row->email];
                }
            }

            return ['account' => $user->public_id, 'revision' => $user->preference_revision, 'locale' => $locale['value'], 'theme' => $theme['value'],
                'categories' => $canReadAddresses ? app(DeliveryRouting::class)->own($connection, $user)['categories'] : [], 'recipients' => $recipients];
        });
    }

    public function updateAccount(string $publicId, string $locale, string $theme, array $delivery, int $revision): array
    {
        new TrustedPrincipal($publicId);
        if ($delivery !== [] && ! $this->allowsAddresses()) {
            throw new OperationRejected('permission_denied', 'runa.auth', 'preferences.admin_write', 403);
        }
        if (! array_is_list($delivery) || count($delivery) > 128) {
            throw new OperationRejected('preference_invalid', 'runa.auth', 'preferences.admin_write', 422);
        }

        return $this->target($publicId, function (Connection $connection, User $user) use ($locale, $theme, $delivery, $revision): array {
            $this->revision($user, $revision);
            foreach (['locale' => $locale, 'theme' => $theme] as $key => $value) {
                $scope = new PreferenceScope('runa', 'runa.auth', 'global', $key);
                if ($this->readLocked($connection, $user, $scope)['value'] !== $value) {
                    $this->writeLocked($connection, $user, $scope, new PreferenceValue($value), $user->preference_revision);
                }
            }
            $routing = app(DeliveryRouting::class);
            $current = $routing->own($connection, $user)['categories'];
            $seen = [];
            foreach ($delivery as $entry) {
                if (! is_array($entry) || count($entry) !== 4 || ! is_string($entry['module'] ?? null) || ! is_string($entry['key'] ?? null)
                    || ! is_bool($entry['enabled'] ?? null) || ! is_array($entry['recipients'] ?? null) || ! array_is_list($entry['recipients'])
                    || ($entry['enabled'] && $entry['recipients'] === []) || count(array_unique($entry['recipients'], SORT_REGULAR)) !== count($entry['recipients']) || count($entry['recipients']) > 100 || array_filter($entry['recipients'], fn ($id) => ! is_string($id)) !== []) {
                    throw new OperationRejected('preference_invalid', 'runa.auth', 'preferences.admin_write', 422);
                }
                $key = $entry['module'].':'.$entry['key'];
                $before = array_values(array_filter($current, fn ($item) => $item['module'] === $entry['module'] && $item['key'] === $entry['key']))[0] ?? null;
                if (isset($seen[$key]) || $before === null) {
                    throw new OperationRejected('delivery_category_unavailable', 'runa.auth', 'preferences.admin_write', 422);
                }
                $seen[$key] = true;
                if ($before['enabled'] !== $entry['enabled'] || $before['recipients'] !== $entry['recipients']) {
                    $routing->configure($connection, $user, ['module' => $entry['module'], 'category' => $entry['key'], 'enabled' => $entry['enabled'],
                        'recipients' => $entry['recipients'], 'revision' => $user->preference_revision]);
                }
            }
            $this->transactions->record('preferences.admin_changed', $user, ['preference_revision' => ['old' => $revision, 'new' => $user->preference_revision]]);

            return ['account' => $user->public_id, 'revision' => $user->preference_revision];
        });
    }

    public function read(PreferenceScope $scope): array
    {
        return $this->target(null, fn (Connection $connection, User $user): array => $this->readLocked($connection, $user, $scope));
    }

    public function write(PreferenceScope $scope, PreferenceValue $value, int $revision): array
    {
        return $this->target(null, fn (Connection $connection, User $user): array => $this->writeLocked($connection, $user, $scope, $value, $revision));
    }

    /** @return PreferenceRead */
    private function writeLocked(Connection $connection, User $user, PreferenceScope $scope, PreferenceValue $value, int $revision): array
    {
        $this->revision($user, $revision);
        [$provider, $definition] = $this->declarations->resolve($scope);
        $this->declarations->validate($provider, $definition, $value->value);
        $before = $this->readLocked($connection, $user, $scope)['value'];
        $connection->table('runa_auth.preferences')->updateOrInsert(['user_id' => $user->id, 'scope' => $scope->id()],
            ['module' => $scope->module, 'value' => json_encode($value->value, JSON_THROW_ON_ERROR), 'definition_version' => $definition->version,
                'updated_at' => now(), 'created_at' => now()]);
        $user->preference_revision++;
        $user->save();
        $this->recordPreference($user, $scope, $before, $value->value, 'preferences.changed');

        return $this->readLocked($connection, $user, $scope);
    }

    public function reset(?string $module, ?PreferenceScope $scope, int $revision): array
    {
        if ($module !== null) {
            new ModuleIdentity($module);
        }
        if ($scope !== null && $module !== $scope->module) {
            throw new \InvalidArgumentException('Inconsistent reset scope.');
        }

        return $this->target(null, fn (Connection $connection, User $user): array => $this->resetLocked($connection, $user, $module, $scope, $revision, false));
    }

    public function resetAccount(string $publicId, int $revision): array
    {
        new TrustedPrincipal($publicId);

        return $this->target($publicId, fn (Connection $connection, User $user): array => $this->resetAccountLocked($connection, $user, $revision));
    }

    /** Private owning reset; callers hold the selected account/revision lock. */
    public function resetAccountLocked(Connection $connection, User $user, int $revision): array
    {
        return $this->resetLocked($connection, $user, null, null, $revision, true);
    }

    /**
     * @param  array{operation: string, revision: int, application: string, module?: string, view?: string, key?: string, value?: mixed, values?: list<array{module: string, view: string, key: string, value: mixed}>}  $input
     * @return array<string, mixed>
     */
    public function http(Connection $connection, User $user, array $input): array
    {
        if ($input['operation'] === 'reset_all') {
            if (isset($input['module']) || isset($input['view']) || isset($input['key'])) {
                throw new OperationRejected('invalid_reset_scope', 'runa.auth', 'preferences', 422);
            }

            return $this->target(null, fn (Connection $connection, User $user): array => $this->resetLocked($connection, $user, null, null, $input['revision'], false, true));
        }
        $scope = null;
        if (isset($input['key'])) {
            if (! isset($input['module'], $input['view'])) {
                throw new \InvalidArgumentException('Preference scope required.');
            }
            $scope = new PreferenceScope($input['application'], $input['module'], $input['view'], $input['key']);
        }
        if ($input['operation'] === 'write') {
            if ($scope === null) {
                throw new \InvalidArgumentException('Preference scope required.');
            }

            if (! array_key_exists('value', $input)) {
                throw new \InvalidArgumentException('Preference value required.');
            }

            return $this->write($scope, new PreferenceValue($input['value']), $input['revision']);
        }
        if ($input['operation'] === 'reset') {
            return $this->reset($input['module'] ?? null, $scope, $input['revision']);
        }
        if ($input['operation'] === 'adopt') {
            $this->revision($user, $input['revision']);
            if ($user->preference_legacy_claimed) {
                throw new IdentityConflict('preferences_already_adopted');
            }
            assert(isset($input['values']));
            foreach ($input['values'] as $entry) {
                $candidate = new PreferenceScope($input['application'], $entry['module'], $entry['view'], $entry['key']);
                $current = $this->readLocked($connection, $user, $candidate);
                if (! $current['stored']) {
                    $result = $this->write($candidate, new PreferenceValue($entry['value']), $user->preference_revision);
                    $user->preference_revision = $result['revision'];
                }
            }
            $user->preference_legacy_claimed = true;
            $user->save();
        }

        return $this->snapshots->all($connection, $user, $input['application']);
    }

    /** @return PreferenceRead */
    public function readLocked(Connection $connection, User $user, PreferenceScope $scope): array
    {
        return $this->snapshots->one($connection, $user, $scope);
    }

    /** @return array{account: string, revision: int} */
    private function resetLocked(Connection $connection, User $user, ?string $module, ?PreferenceScope $scope, int $revision, bool $admin, bool $includeDelivery = false): array
    {
        $this->revision($user, $revision);
        if ($scope !== null) {
            $this->declarations->resolve($scope);
        }
        $query = $connection->table('runa_auth.preferences')->where('user_id', $user->id);
        if ($module !== null) {
            $query->where('module', $module);
        }
        if ($scope !== null) {
            $query->where('scope', $scope->id());
        }
        // Resolve old effective values before deletion, preserving source defaults and migrations.
        $resets = [];
        /** @var \stdClass&object{scope: string, value: string} $row */
        foreach ($query->get() as $row) {
            [$application, $owner, $view, $key] = explode(':', $row->scope);
            $candidate = new PreferenceScope($application, $owner, $view, $key);
            if ($this->displayOnly($candidate)) {
                continue;
            }
            // Unavailable/removed declarations have no trustworthy current default.
            try {
                [, $definition] = $this->declarations->resolve($candidate);
                $before = $this->readLocked($connection, $user, $candidate)['value'];
                $resets[] = [$candidate, $before, $definition->default];
            } catch (OperationRejected) {
                $resets[] = [$candidate, json_decode($row->value, true, flags: JSON_THROW_ON_ERROR), null];
            }
        }
        $query->delete();
        foreach ($resets as [$candidate, $before, $after]) {
            $this->recordPreference($user, $candidate, $before, $after, 'preferences.reset');
        }
        $notificationOverrides = 0;
        if ($includeDelivery) {
            $notificationOverrides = $connection->table('runa_auth.delivery_preferences')->where('user_id', $user->id)->count();
            app(DeliveryRouting::class)->recordReset($connection, $user);
            $connection->table('runa_auth.delivery_preferences')->where('user_id', $user->id)->delete();
        }
        $user->preference_revision++;
        $user->preference_legacy_claimed = true;
        $user->save();
        if ($admin) {
            $this->transactions->record('preferences.admin_reset', $user, ['presentation_overrides' => ['old' => count($resets), 'new' => 0]]);
        } elseif ($includeDelivery) {
            $this->transactions->record('preferences.reset_all', $user, ['notification_overrides' => ['old' => $notificationOverrides, 'new' => 0], 'presentation_overrides' => ['old' => count($resets), 'new' => 0]]);
        }

        return ['account' => $user->public_id, 'revision' => $user->preference_revision];
    }

    private function allowsAddresses(): bool
    {
        $context = $this->scope->current();

        return $context !== null && $context->moduleId === 'runa.auth' && $this->authorizer->allows($context, 'runa.auth.addresses.read');
    }

    private function displayOnly(PreferenceScope $scope): bool
    {
        return $scope->module === 'runa.auth' && $scope->view === 'global' && in_array($scope->key, ['locale', 'theme'], true);
    }

    private function recordPreference(User $user, PreferenceScope $scope, mixed $before, mixed $after, string $action): void
    {
        if (! $this->displayOnly($scope) && $before !== $after) {
            $this->transactions->record($action, $user, ['setting' => ['old' => $scope->id(), 'new' => $scope->id()],
                'value' => ['old' => $before, 'new' => $after]]);
        }
    }

    private function revision(User $user, int $revision): void
    {
        if ($revision !== $user->preference_revision) {
            throw new IdentityConflict('preferences_stale');
        }
    }

    /**
     * @template T
     *
     * @param  \Closure(Connection, User): T  $work
     * @return T
     */
    private function target(?string $target, \Closure $work): mixed
    {
        return $this->transactions->run(function (Connection $connection) use ($target, $work): mixed {
            $this->transactions->lockAuthority($connection);
            $id = $target ?? $this->principals->current()?->publicId;
            $user = $id === null ? null : ($target === null ? User::query() : User::withTrashed())->where('public_id', $id)->lockForUpdate()->first();
            if ($user === null || ($target === null && ! app(LookupIdentity::class)->evaluate($user, CarbonImmutable::now('UTC'))->mayOperate())) {
                throw new OperationRejected('authentication_required', 'runa.auth', 'preferences', 401);
            }

            return $work($connection, $user);
        });
    }
}
