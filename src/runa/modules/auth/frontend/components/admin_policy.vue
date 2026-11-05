<script setup lang="ts">
import { AdminSettingsPage } from '@/runa/admin';
import type {
  AdminFormController,
  AdminPageController,
  AdminSettingsGroup,
} from '@/runa/admin';
defineProps<{ controller: AdminPageController; form: AdminFormController }>();
const groups: AdminSettingsGroup[] = [
  {
    key: 'entry',
    title: { pl: 'Logowanie i rejestracja', en: 'Sign-in and registration' },
    fields: [
      'public_welcome_enabled',
      'identifier_mode',
      'public_registration',
      'registration_activation',
      'email_verification',
      'reuse_deleted_identifiers',
    ],
  },
  {
    key: 'passwords',
    title: { pl: 'Hasła i odzyskiwanie dostępu', en: 'Passwords and recovery' },
    fields: [
      'password_min_length',
      'password_history',
      'password_mixed_case',
      'password_numbers',
      'password_symbols',
      'password_expiry_days',
      'password_session_policy',
      'reset_minutes',
      'password_policy_effect',
    ],
  },
  {
    key: 'factors',
    title: {
      pl: 'Drugi składnik i zaufane urządzenia',
      en: 'Second factors and trusted devices',
    },
    fields: [
      'two_factor_required',
      'totp_enabled',
      'recovery_codes_enabled',
      'email_otp_enabled',
      'email_otp_minutes',
      'trusted_devices_enabled',
      'trusted_device_days',
    ],
  },
  {
    key: 'sessions',
    title: {
      pl: 'Sesje i potwierdzanie operacji',
      en: 'Sessions and operation confirmation',
    },
    fields: [
      'session_limit',
      'session_idle_minutes',
      'session_absolute_minutes',
      'session_remember_minutes',
      'session_extension',
      'session_heartbeat_seconds',
      'session_warning_seconds',
      'step_up_seconds',
      'security_attempt_limit',
    ],
  },
  {
    key: 'abuse',
    title: {
      pl: 'Ochrona przed próbami logowania',
      en: 'Login attempt protection',
    },
    fields: [
      'abuse_window_seconds',
      'abuse_account_limit',
      'abuse_source_limit',
      'abuse_lock_seconds',
      'abuse_escalation_limit',
    ],
  },
  {
    key: 'profile',
    title: {
      pl: 'Profil i samodzielna obsługa konta',
      en: 'Profile and account self-service',
    },
    fields: [
      'own_name_editable',
      'own_login_editable',
      'own_email_editable',
      'own_login_history',
      'own_account_history',
      'self_deactivate',
      'self_delete',
      'avatar_enabled',
      'avatar_editor',
      'avatar_max_kib',
    ],
  },
  {
    key: 'emails',
    title: {
      pl: 'Zmiana i dodatkowe adresy e-mail',
      en: 'Email changes and secondary addresses',
    },
    fields: [
      'email_change_old_confirmation',
      'email_change_minutes',
      'email_change_session_policy',
      'admin_email_verification_bypass',
      'secondary_emails_enabled',
      'secondary_self_add',
      'secondary_self_change',
      'secondary_self_remove',
      'secondary_email_sharing',
      'secondary_email_limit',
    ],
  },
  {
    key: 'invitations',
    title: {
      pl: 'Zaproszenia i weryfikacja',
      en: 'Invitations and verification',
    },
    fields: [
      'invitation_minutes',
      'verification_minutes',
      'invitation_reminder_minutes',
      'invitation_reminder_count',
    ],
  },
  {
    key: 'notices',
    title: {
      pl: 'Powiadomienia i przypomnienia',
      en: 'Notifications and reminders',
    },
    fields: [
      'notice_new_browser',
      'notice_failed_login',
      'notice_locked',
      'notice_password_changed',
      'notice_account_deactivation',
      'deactivation_admin_copy',
      'notice_password_expiry',
      'notice_interval_minutes',
      'expiry_start_days',
      'expiry_interval_hours',
      'expiry_max_count',
    ],
  },
];
</script>
<template>
  <AdminSettingsPage :controller="controller" :form="form" :groups="groups" />
</template>
