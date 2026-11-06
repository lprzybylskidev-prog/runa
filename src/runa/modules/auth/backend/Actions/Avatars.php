<?php

namespace Runa\Modules\Auth\Actions;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Runa\Contracts\Modules\AuthorityGuard;
use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\ModuleFramework\Api\OperationScope;
use Runa\Modules\Auth\Contracts\IdentityConflict;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Modules\Auth\Support\SecurityContext;
use Runa\Support\Images\RasterImageContent;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Storage\ModuleStorage;

final readonly class Avatars
{
    public function __construct(private SecurityContext $context, private Profiles $profiles, private EntryTransaction $transactions,
        private ModuleStorage $storage, private AuthorityGuard $authority, private OperationAuthorizer $authorizer, private OperationScope $scope) {}

    /**
     * @return array{contents: string|null, type: string|null}
     */
    public function read(Request $request, bool $admin = false): array
    {
        if ($admin) {
            Validator::make($request->query(), ['public_id' => ['required', 'uuid:7']])->validate();
        }

        return $this->context->run($request, function (Connection $connection, User $user) use ($request, $admin): array {
            if ($admin) {
                $context = $this->scope->current();
                if ($context === null || $context->moduleId !== 'runa.auth' || ! $this->authorizer->allows($context, 'runa.auth.avatars.manage')
                    || ! in_array($this->profiles->value('avatar_editor'), ['admin', 'both'], true)) {
                    $this->refuse('permission_denied');
                }
                $target = User::withTrashed()->where('public_id', $request->string('public_id')->toString())->first();
                if ($target === null || ($target->trashed() && ! $this->authorizer->allows($context, 'runa.auth.users.deleted.read'))) {
                    $this->refuse('avatar_missing', 404);
                }
                $user = $target;
            }
            if (! $this->profiles->value('avatar_enabled') || $user->avatar_path === null) {
                $this->refuse('avatar_missing', 404);
            }

            return ['contents' => $this->storage->disk('runa.auth')->get($user->avatar_path), 'type' => $user->avatar_type];
        }, count: false, unrestricted: true);
    }

    /**
     * @return array{updated: bool, version: int}
     */
    public function upload(Request $request, bool $admin = false): array
    {
        if (app('db')->connection()->transactionLevel() !== 0) {
            throw new \LogicException('Avatar upload requires a root operation.');
        }
        $file = $request->file('avatar');
        // Validate decoded file headers, MIME content, dimensions and bounded bytes; never trust filename/extension.
        Validator::make(['avatar' => $file], ['avatar' => ['required', File::image()->types(['jpg', 'png', 'webp'])
            ->max($this->profiles->integer('avatar_max_kib'))->dimensions(Rule::dimensions()->minWidth(1)->minHeight(1)->maxWidth(4096)->maxHeight(4096))]])->validate();
        if (! $file instanceof UploadedFile) {
            $this->refuse('avatar_invalid', 422);
        }
        $contents = $file->getContent();
        $type = $file->getMimeType();
        if (! is_string($type) || ! RasterImageContent::safe($contents, $type)) {
            $this->refuse('avatar_invalid', 422);
        }
        $extension = match ($type) {
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', default => throw new \InvalidArgumentException('Invalid avatar content.')
        };
        // Commit an unreferenced cleanup candidate before writing storage. Crash/failure cannot leave an untracked blob.
        $path = $this->target($request, $admin, function (Connection $connection, User $user) use ($extension): string {
            $path = 'avatars/'.$user->public_id.'/'.Str::uuid7().'.'.$extension;
            $connection->table('runa_auth.avatar_files')->insert(['user_id' => $user->id, 'path' => $path, 'available_at' => now()->addMinutes(30), 'created_at' => now()]);

            return $path;
        });
        $this->storage->disk('runa.auth')->put($path, $contents);
        $result = $this->target($request, $admin, function (Connection $connection, User $user) use ($path, $type): array {
            $candidate = $connection->table('runa_auth.avatar_files')->where('path', $path)->where('user_id', $user->id)->where('available_at', '>', now())->first();
            if ($candidate === null) {
                $this->refuse('avatar_upload_expired', 409);
            }
            $old = $user->avatar_path;
            $user->avatar_path = $path;
            $user->avatar_type = $type;
            $user->version++;
            $user->save();
            if ($old !== null) {
                $connection->table('runa_auth.avatar_files')->where('path', $old)->update(['available_at' => now()]);
            }
            $this->transactions->record('avatar.changed', $user, ['has_avatar' => ['old' => $old !== null, 'new' => true]]);

            return ['updated' => true, 'version' => $user->version];
        });
        // The published user/Audit transaction has committed; storage deletion is retryable technical cleanup.
        try {
            $this->cleanup();
        } catch (\Throwable) { /* The durable cleanup schedule retries. */
        }

        return $result;
    }

    /**
     * @return array{updated: bool, version: int}
     */
    public function remove(Request $request, bool $admin = false): array
    {
        if (app('db')->connection()->transactionLevel() !== 0) {
            throw new \LogicException('Avatar removal requires a root operation.');
        }
        $result = $this->target($request, $admin, function (Connection $connection, User $user): array {
            return $this->removeLocked($connection, $user);
        });
        try {
            $this->cleanup();
        } catch (\Throwable) { /* The durable cleanup schedule retries. */
        }

        return $result;
    }

    /** Private metadata/Audit phase; durable cleanup owns storage after commit.
     * @return array{updated:bool,version:int} */
    public function removeLocked(Connection $connection, User $user): array
    {
        if ($user->avatar_path !== null) {
            $connection->table('runa_auth.avatar_files')->where('path', $user->avatar_path)->update(['available_at' => now()]);
            $user->avatar_path = null;
            $user->avatar_type = null;
            $user->version++;
            $user->save();
            $this->transactions->record('avatar.removed', $user, ['has_avatar' => ['old' => true, 'new' => false]]);
        }

        return ['updated' => true, 'version' => $user->version];
    }

    public function cleanup(): void
    {
        $this->transactions->run(function (Connection $connection): void {
            $this->transactions->lockAuthority($connection);
            $rows = $connection->table('runa_auth.avatar_files')->where('available_at', '<=', now())->whereNotExists(fn (Builder $q) => $q->selectRaw('1')->from('runa_auth.users')->whereColumn('avatar_path', 'runa_auth.avatar_files.path'))
                ->orderBy('id')->limit(100)->get();
            $deleted = [];
            /** @var \stdClass&object{id:int,path: string} $row */
            foreach ($rows as $row) {
                try {
                    $this->storage->disk('runa.auth')->delete($row->path);
                } catch (\Throwable) {
                    continue;
                } // Keep the exact durable candidate for the existing cleanup schedule.
                $deleted[] = $row->id;
            }
            if ($deleted !== []) {
                $connection->table('runa_auth.avatar_files')->whereIn('id', $deleted)->delete();
            }
        });
    }

    /**
     * @template T
     *
     * @param  Closure(Connection, User): T  $work
     * @return T
     */
    private function target(Request $request, bool $admin, Closure $work): mixed
    {
        return $this->context->run($request, function (Connection $connection, User $actor) use ($request, $admin, $work): mixed {
            $editor = $this->profiles->value('avatar_editor');
            if (! $this->profiles->value('avatar_enabled') || ! in_array($editor, [$admin ? 'admin' : 'user', 'both'], true)) {
                $this->refuse('avatar_edit_disabled');
            }
            $user = $actor;
            if ($admin) {
                $publicId = $request->string('public_id')->toString();
                new TrustedPrincipal($publicId);
                $user = User::where('public_id', $publicId)->lockForUpdate()->first() ?? throw new IdentityConflict('identity_missing');
                $this->authority->assertMayRemove($connection, []);
            }
            $this->profiles->version($user, $request->integer('version'));

            return $work($connection, $user);
        }, count: false, unrestricted: true);
    }

    private function refuse(string $code, int $status = 403): never
    {
        throw new OperationRejected($code, 'runa.auth', 'avatar', $status);
    }
}
