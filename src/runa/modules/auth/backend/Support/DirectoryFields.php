<?php

namespace Runa\Modules\Auth\Support;

/** Private Auth-owned directory facts; presentation and query authority share one list. */
final class DirectoryFields
{
    /** @return array<string,array{string,string,string,?string}> label, type, grant, feature */
    public static function definitions(): array
    {
        return [
            'display_name' => ['Display name', 'text', 'users.read', null],
            'state' => ['Account state', 'dictionary', 'users.read', null],
            'email' => ['Email', 'text', 'emails.read', null],
            'login' => ['Login', 'text', 'profiles.read', null],
            'first_name' => ['First name', 'text', 'profiles.read', null],
            'surname' => ['Surname', 'text', 'profiles.read', null],
            'active' => ['Active', 'boolean', 'users.read', null],
            'approved' => ['Approved', 'boolean', 'users.read', null],
            'security_locked' => ['Security Locked', 'boolean', 'users.read', null],
            'logged_in' => ['Logged in', 'boolean', 'sessions.manage', null],
            'session_count' => ['Live session count', 'integer', 'sessions.manage', null],
            'verification_required' => ['Verification required', 'boolean', 'emails.read', 'verification'],
            'operator_recovery_pending' => ['Operator recovery pending', 'boolean', 'passwords.reset', null],
            'has_avatar' => ['Avatar set', 'boolean', 'avatars.manage', 'avatar'],
            'trusted_device_count' => ['Trusted device count', 'integer', 'trust.manage', 'trust'],
            'verified' => ['Verified', 'boolean', 'emails.read', 'verification'],
            'email_verified_at' => ['Email verified at', 'datetime', 'emails.read', 'verification'],
            'two_factor_enabled' => ['Two-factor authentication enabled', 'boolean', 'two_factor.reset', 'factors'],
            'two_factor_required' => ['Two-factor authentication required', 'boolean', 'two_factor.reset', 'factors'],
            'totp_setup_pending' => ['TOTP setup pending', 'boolean', 'two_factor.reset', 'totp'],
            'totp_pending_until' => ['TOTP setup expires at', 'datetime', 'two_factor.reset', 'totp'],
            'last_login_at' => ['Latest retained login', 'datetime', 'sessions.manage', null],
            'last_activity_at' => ['Latest retained activity', 'datetime', 'sessions.manage', null],
            'totp_enrolled' => ['TOTP configured', 'boolean', 'two_factor.reset', 'totp'],
            'email_factor_enrolled' => ['Email factor configured', 'boolean', 'two_factor.reset', 'email_factor'],
            'recovery_codes_remaining' => ['Recovery codes remaining', 'integer', 'two_factor.reset', 'recovery'],
            'password_set' => ['Password set', 'boolean', 'passwords.reset', null],
            'password_changed_at' => ['Password changed at', 'datetime', 'passwords.reset', null],
            'password_change_required' => ['Password Change Required', 'boolean', 'users.read', 'password_expiry'],
            'password_expires_at' => ['Password expires at', 'datetime', 'passwords.reset', 'password_expiry'],
            'password_expired' => ['Password expired', 'boolean', 'passwords.reset', 'password_expiry'],
            'invitation_pending' => ['Pending invitation', 'boolean', 'invitations.manage', null],
            'invitation_expires_at' => ['Invitation expires at', 'datetime', 'invitations.manage', null],
            'invitation_sent_at' => ['Invitation sent at', 'datetime', 'invitations.manage', null],
            'created_at' => ['Created at', 'datetime', 'users.read', null],
            'updated_at' => ['Updated at', 'datetime', 'users.read', null],
            'deleted_at' => ['Deleted at', 'datetime', 'users.deleted.read', null],
            'authenticated_at' => ['Latest live login', 'datetime', 'sessions.manage', null],
            'activity_at' => ['Latest session activity', 'datetime', 'sessions.manage', null],
            'activate_at' => ['Activate At', 'datetime', 'users.read', null],
            'deactivate_at' => ['Deactivate At', 'datetime', 'users.read', null],
            'expires_at' => ['Expires At', 'datetime', 'users.read', null],
            'attempt_locked_until' => ['Attempt Locked Until', 'datetime', 'users.read', null],
        ];
    }
}
