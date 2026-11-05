<?php

namespace Runa\Modules\Auth\Support;

use Runa\Support\Modules\Settings\ModuleSettings;

/** Current Auth configuration, shared by declarations and owning projections. */
final readonly class AdministrationFeatures
{
    public function __construct(private ModuleSettings $settings) {}

    public function enabled(string $feature): bool
    {
        return match ($feature) {
            'verification' => $this->settings->value('runa.auth', 'email_verification') !== 'disabled',
            'password_expiry' => $this->settings->integer('runa.auth', 'password_expiry_days') > 0,
            'factors' => $this->enabled('totp') || $this->enabled('email_factor'),
            'totp' => $this->settings->boolean('runa.auth', 'totp_enabled'),
            'email_factor' => $this->settings->boolean('runa.auth', 'email_otp_enabled'),
            'recovery' => $this->enabled('totp') && $this->settings->boolean('runa.auth', 'recovery_codes_enabled'),
            'trust' => $this->settings->boolean('runa.auth', 'trusted_devices_enabled'),
            'avatar' => $this->settings->boolean('runa.auth', 'avatar_enabled') && in_array($this->settings->value('runa.auth', 'avatar_editor'), ['admin', 'both'], true),
            'secondary' => $this->settings->boolean('runa.auth', 'secondary_emails_enabled'),
            'email_bypass' => $this->enabled('verification') && $this->settings->boolean('runa.auth', 'admin_email_verification_bypass'),
            default => throw new \InvalidArgumentException('Unknown Auth administration feature.'),
        };
    }

    public function field(string $key): bool
    {
        $feature = match ($key) {
            'verified', 'verification_required', 'email_verified_at' => 'verification',
            'password_change_required', 'password_expires_at' => 'password_expiry',
            'replacement_email' => 'email_bypass',
            'totp_enrolled' => 'totp', 'email_factor_enrolled' => 'email_factor',
            'recovery_codes_remaining' => 'recovery',
            default => null,
        };

        return $feature === null || $this->enabled($feature);
    }

    public function command(string $section, string $command): bool
    {
        return match ($section.'.'.$command) {
            'primary_email.require_verification' => $this->enabled('verification'),
            'primary_email.replace', 'primary_email.verify' => $this->enabled('email_bypass'),
            'secondary.verify' => $this->enabled('secondary') && $this->settings->boolean('runa.auth', 'admin_email_verification_bypass'),
            'security.factor_reset' => $this->enabled('factors'),
            default => true,
        };
    }

    public function lifecycle(string $operation): bool
    {
        return match ($operation) {
            'require_password_change' => $this->enabled('password_expiry'),
            default => true,
        };
    }
}
