<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Runa\Support\Modules\Data\ModuleSchema;
use Runa\Support\Modules\ModuleIdentity;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection();
        $schema = new ModuleSchema($connection, new ModuleIdentity('runa.auth'));
        $schema->create('users', function (Blueprint $table): void {
            foreach (['name', 'first_name', 'surname', 'login'] as $column) {
                $table->text($column)->nullable();
            }
            $table->text('email');
            $table->text('email_claim')->nullable()->unique('auth_users_email_claim');
            $table->text('login_claim')->nullable()->unique('auth_users_login_claim');
            $table->text('password')->nullable();
            $table->text('remember_token')->nullable();
            $table->boolean('active')->default(false);
            $table->boolean('approved')->default(false);
            $table->boolean('security_locked')->default(false);
            $table->boolean('password_change_required')->default(false);
            $table->bigInteger('version')->default(1);
            // Revocation generation: operational sessions/trust must bind this value.
            $table->bigInteger('security_version')->default(1);
            foreach (['activate_at', 'deactivate_at', 'attempt_locked_until', 'email_verified_at'] as $column) {
                $table->timestampTz($column, 6)->nullable();
            }
            $table->timestampsTz(6);
            $table->softDeletesTz('deleted_at', 6);

            $table->timestampTz('password_changed_at', 6)->nullable();
            $table->text('totp_secret')->nullable();
            $table->text('totp_pending')->nullable();
            $table->timestampTz('totp_pending_until', 6)->nullable();
            $table->bigInteger('totp_counter')->nullable();
            $table->boolean('email_factor')->default(false);
            $table->jsonb('recovery_hashes')->default('[]');
            $table->text('avatar_path')->nullable();
            $table->text('avatar_type')->nullable();
            $table->boolean('verification_required')->default(true);
            $table->boolean('verification_forced')->default(false);
            $table->bigInteger('preference_revision')->default(0);
            $table->boolean('preference_legacy_claimed')->default(false);
            $table->boolean('operator_recovery_pending')->default(false);
        });
        $connection->statement(<<<'SQL'
                    ALTER TABLE runa_auth.users
                    ADD CHECK ((name IS NOT NULL AND first_name IS NULL AND surname IS NULL)
                        OR (name IS NULL AND first_name IS NOT NULL AND surname IS NOT NULL)),
                    ADD CHECK (name IS NULL OR (name = btrim(name) AND char_length(name) BETWEEN 1 AND 160 AND name !~ '[[:cntrl:]]')),
                    ADD CHECK (first_name IS NULL OR (first_name = btrim(first_name) AND char_length(first_name) BETWEEN 1 AND 160 AND first_name !~ '[[:cntrl:]]')),
                    ADD CHECK (surname IS NULL OR (surname = btrim(surname) AND char_length(surname) BETWEEN 1 AND 160 AND surname !~ '[[:cntrl:]]')),
                    ADD CHECK (email = lower(btrim(email)) AND octet_length(email) BETWEEN 3 AND 254 AND email ~ '^[!-~]+@[!-~]+$'),
                    ADD CHECK (login IS NULL OR login ~ '^[a-z0-9][a-z0-9._-]{0,63}$'),
                    ADD CHECK (email_claim IS NULL OR email_claim = email),
                    ADD CHECK (login_claim IS NULL OR (login IS NOT NULL AND login_claim = login)),
                    ADD CHECK (deleted_at IS NOT NULL OR (email_claim IS NOT NULL AND (login IS NULL OR login_claim IS NOT NULL))),
                    ADD CHECK (deleted_at IS NULL OR NOT active),
                    ADD CHECK (version > 0 AND security_version > 0),
                    ADD CHECK (activate_at IS NULL OR deactivate_at IS NULL OR activate_at < deactivate_at)
                    SQL);
        DB::statement("ALTER TABLE runa_auth.users ADD CHECK (jsonb_typeof(recovery_hashes) = 'array'), ADD CHECK (totp_counter IS NULL OR totp_counter >= 0), ADD CHECK ((totp_pending IS NULL) = (totp_pending_until IS NULL))");
        DB::statement("ALTER TABLE runa_auth.users ADD CHECK ((avatar_path IS NULL) = (avatar_type IS NULL)), ADD CHECK (avatar_type IS NULL OR avatar_type IN ('image/jpeg', 'image/png', 'image/webp'))");
        DB::statement('ALTER TABLE runa_auth.users ADD CHECK (preference_revision >= 0)');
    }

    public function down(): void
    {
        throw new RuntimeException('Module table recovery requires an explicit plan.');
    }
};
