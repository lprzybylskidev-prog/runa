<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Auth\Contracts\AuthDelivery;
use Runa\Modules\Auth\Contracts\DeliveryCategory;
use Runa\Modules\Auth\Contracts\DeliveryProvider;
use Runa\Modules\Auth\Contracts\IdentityConflict;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Support\Modules\Api\ModuleDeclarations;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\ModuleAvailability;
use Runa\Support\Modules\ModuleIdentity;
use Runa\Support\Modules\ModuleRegistry;
use Runa\Support\Modules\Settings\ModuleSettings;

final readonly class DeliveryRouting implements AuthDelivery
{
    public function __construct(private EntryTransaction $transactions, private ModuleRegistry $registry,
        private ModuleDeclarations $declarations, private ModuleAvailability $availability,
        private LookupIdentity $identities, private ModuleSettings $settings) {}

    public function category(string $module, string $key): DeliveryCategory
    {
        new ModuleIdentity($module);
        $record = $this->registry->snapshot()->modules[$module] ?? null;
        if ($record !== null && $this->availability->statuses($module)[$module]->available) {
            foreach ($this->declarations->classes($record, 'settings') as $class) {
                $provider = $this->declarations->make($record, $class);
                if (! $provider instanceof DeliveryProvider) {
                    continue;
                }
                $seen = [];
                $found = null;
                foreach ($provider->deliveryCategories() as $category) {
                    if (! $category instanceof DeliveryCategory || count($seen) >= 128 || isset($seen[$category->key])) {
                        throw new \RuntimeException('Invalid delivery declarations.');
                    }
                    $seen[$category->key] = true;
                    if ($category->key === $key) {
                        $found = $category;
                    }
                }
                if ($found !== null) {
                    return $found;
                }
            }
        }
        throw new OperationRejected('delivery_category_unavailable', 'runa.auth', 'delivery', 422);
    }

    public function recipients(string $publicId, string $module, string $category): array
    {
        new TrustedPrincipal($publicId);

        return $this->transactions->run(function (Connection $connection) use ($publicId, $module, $category): array {
            $this->transactions->lockAuthority($connection);
            $user = User::where('public_id', $publicId)->lockForUpdate()->first();
            if ($user === null || ! $this->identities->evaluate($user, CarbonImmutable::now('UTC'))->mayOperate()) {
                return [];
            }

            return $this->locked($connection, $user, $module, $this->category($module, $category));
        });
    }

    public function locked(Connection $connection, User $user, string $module, DeliveryCategory $category): array
    {
        // Sensitive/mandatory categories are primary-only and cannot be user-disabled.
        if ($category->mandatory || $category->sensitive) {
            return [$user->email];
        }
        $preference = $connection->table('runa_auth.delivery_preferences')->where('user_id', $user->id)->where('module', $module)->where('category', $category->key)->first();
        if ($preference !== null && ! $preference->enabled) {
            return [];
        }
        $selected = $preference === null ? ['primary'] : json_decode($preference->recipients, true, flags: JSON_THROW_ON_ERROR);
        $result = in_array('primary', $selected, true) && $user->email_verified_at !== null ? [$user->email] : [];
        if ($this->settings->value('runa.auth', 'secondary_emails_enabled')) {
            $secondary = $connection->table('runa_auth.secondary_emails')->where('user_id', $user->id)->whereIn('public_id', array_values(array_filter($selected, fn ($id) => $id !== 'primary')))
                ->whereNull('deleted_at')->whereNotNull('verified_at')->pluck('email')->all();
            $result = array_merge($result, $secondary);
        }

        return array_values(array_unique($result));
    }

    public function configure(Connection $connection, User $user, array $input): array
    {
        $category = $this->category($input['module'], $input['category']);
        if ($user->preference_revision !== $input['revision']) {
            throw new IdentityConflict('preferences_stale');
        }
        if (($category->mandatory || $category->sensitive) && (! $input['enabled'] || $input['recipients'] !== ['primary'])) {
            throw new OperationRejected('mandatory_delivery', 'runa.auth', 'delivery', 422);
        }
        $available = ['primary'];
        if ($this->settings->value('runa.auth', 'secondary_emails_enabled')) {
            $available = array_merge($available, $connection->table('runa_auth.secondary_emails')->where('user_id', $user->id)->whereNull('deleted_at')->whereNotNull('verified_at')->pluck('public_id')->all());
        }
        if (array_diff($input['recipients'], $available) !== []) {
            throw new OperationRejected('recipient_unavailable', 'runa.auth', 'delivery', 422);
        }
        $before = $connection->table('runa_auth.delivery_preferences')->where('user_id', $user->id)->where('module', $input['module'])->where('category', $category->key)->first();
        $connection->table('runa_auth.delivery_preferences')->updateOrInsert(['user_id' => $user->id, 'module' => $input['module'], 'category' => $category->key],
            ['enabled' => $input['enabled'], 'recipients' => json_encode($input['recipients'], JSON_THROW_ON_ERROR), 'updated_at' => now(), 'created_at' => now()]);
        $user->preference_revision++;
        $user->save();
        $resolve = function (array $ids) use ($connection, $user): array {
            $addresses = [];
            foreach ($ids as $id) {
                $addresses[] = $id === 'primary' ? $user->email : $connection->table('runa_auth.secondary_emails')->where('user_id', $user->id)->where('public_id', $id)->value('email');
            }

            return array_values(array_filter($addresses, 'is_string'));
        };
        $this->transactions->record('delivery.preferences_changed', $user, [
            'category' => ['old' => $input['module'].'.'.$category->key, 'new' => $input['module'].'.'.$category->key],
            'enabled' => ['old' => $before?->enabled ?? true, 'new' => $input['enabled']],
            'recipients' => ['old' => $resolve($before === null ? ['primary'] : json_decode($before->recipients, true, flags: JSON_THROW_ON_ERROR)), 'new' => $resolve($input['recipients'])],
        ]);

        return ['account' => $user->public_id, 'revision' => $user->preference_revision, 'channel' => 'email'];
    }

    public function own(Connection $connection, User $user): array
    {
        $categories = [];
        foreach ($this->registry->snapshot()->modules as $record) {
            if (! $record->registered || ! $record->sourcePresent || ! $record->enabled || ! $this->availability->statuses($record->id)[$record->id]->available) {
                continue;
            }
            foreach ($this->declarations->classes($record, 'settings') as $class) {
                $provider = $this->declarations->make($record, $class);
                if (! $provider instanceof DeliveryProvider) {
                    continue;
                }
                foreach ($provider->deliveryCategories() as $category) {
                    $this->category($record->id, $category->key);
                    $preference = $connection->table('runa_auth.delivery_preferences')->where('user_id', $user->id)->where('module', $record->id)->where('category', $category->key)->first();
                    $categories[] = ['module' => $record->id, 'key' => $category->key, 'names' => $category->names,
                        'mandatory' => $category->mandatory, 'sensitive' => $category->sensitive, 'channel' => 'email',
                        'enabled' => $category->mandatory || $category->sensitive || ($preference?->enabled ?? true),
                        'recipients' => $category->mandatory || $category->sensitive ? ['primary'] : ($preference === null ? ['primary'] : json_decode($preference->recipients, true, flags: JSON_THROW_ON_ERROR))];
                }
            }
        }

        return ['account' => $user->public_id, 'revision' => $user->preference_revision, 'categories' => $categories];
    }
}
