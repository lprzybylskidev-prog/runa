/** Source-owned, reviewed copy only. Never display server messages or unknown reasons. */
export const refusalMessages: Record<string, string> = {
  audit_event_missing: 'This audit event is no longer available.',
  history_event_not_found: 'This history event is no longer available.',
  history_cursor_invalid:
    'The history cursor expired. Return to the first page.',
  application_settings_revision_changed:
    'Application settings changed. Review the current values before saving.',
  application_settings_invalid:
    'Invalid application settings. Check the entered values.',
  identifier_conflict:
    'This email address or login is already in use. Choose another.',
  secondary_limit:
    'The secondary address limit has been reached. Remove an address before adding another.',
  secondary_missing:
    'This secondary address was removed. Select a current address.',
  secondary_disabled: 'Secondary email addresses are disabled.',
  secondary_edit_disabled: 'Editing secondary email addresses is disabled.',
  email_unchanged: 'Enter a different email address.',
  email_edit_disabled: 'Changing the primary email address is disabled.',
  email_token_invalid:
    'The verification link is invalid or expired. Send a new link.',
  password_reused: 'Choose a password different from recently used passwords.',
  security_proof_invalid:
    'Identity verification failed. Check your password and current code.',
  recipient_unavailable:
    'The selected recipient is unavailable. Select a current verified address.',
  mandatory_delivery: 'Required notifications cannot be disabled.',
  delivery_category_unavailable: 'This notification category is unavailable.',
  profile_field_readonly: 'Editing this profile field is disabled.',
  login_required: 'A login is required.',
  avatar_invalid:
    'Invalid image. Choose a supported file within the size limit.',
  avatar_upload_expired: 'The uploaded image expired. Select it again.',
  avatar_edit_disabled: 'Avatar editing is disabled.',
  avatar_missing: 'The avatar has already been removed.',
  self_change_disabled: 'Changing your own account state is disabled.',
  preference_invalid: 'Invalid preference value. Choose an available option.',
  preference_undeclared: 'This preference is unavailable.',
  preference_module_unavailable: 'Preferences for this module are unavailable.',
  preferences_already_adopted:
    'Settings from this device have already been adopted.',
  signed_out: 'Your session expired. Sign in again.',
  account_restricted:
    'Your account requires an additional step. Complete it before editing.',
  permission_denied: 'You do not have permission for this operation.',
  entry_token_invalid: 'Verification expired. Confirm the operation again.',
  identity_stale: 'User data changed. Review the current values before saving.',
  identity_conflict:
    'User state changed. Review current data before trying again.',
  identity_missing: 'This account is no longer available.',
  preferences_stale:
    'Preferences changed. Review the current values before saving.',
  policy_stale: 'The policy changed. Review the current rules before saving.',
  exception_stale:
    'The exception changed. Review the current values before saving.',
  last_superadmin_required:
    'At least one active superadministrator must remain.',
  authority_self_escalation:
    'You cannot grant yourself superadministrator authority.',
  authority_change_refused:
    'These privileges cannot be changed. Keep at least one active superadministrator.',
  access_definition_conflict:
    'This role or permission code is already in use. Choose another.',
  access_definition_protected:
    'This role or permission is managed by the system and cannot be changed.',
  access_role_referenced:
    'This role is used by other roles. Remove those references before deleting it.',
  access_reference_protected_or_missing:
    'A selected role or permission is unavailable or protected. Change the selection.',
  access_reference_limit:
    'Too many roles or permissions selected. Reduce the selection.',
  access_effect_limit:
    'This change affects too many permission relationships. Split it into smaller operations.',
  access_deletion_limit:
    'Too many roles would be deleted. Reduce the selection.',
  access_delete_confirmation_required:
    'This role has assignments. Review the effects and confirm deletion.',
  access_subject_missing: 'The selected user is unavailable.',
  access_definition_missing:
    'The selected role or permission is no longer available.',
  access_definition_stale:
    'The role or permission changed. Review current values before saving.',
  access_preview_stale:
    'Roles or permissions changed. Review the operation effects again.',
  admin_revision_stale:
    'Form data changed. Review the current values before saving.',
  admin_review_stale:
    'The operation effects changed. Review them again before confirming.',
  admin_review_required: 'Review the operation effects before confirming.',
  admin_participants_changed:
    'Available form sections changed. Review current data before saving.',
  admin_participant_incompatible:
    'These sections cannot be saved together. Save them separately.',
  admin_binding_unavailable:
    'This form is no longer available. Return to the list and open it again.',
  admin_revision_changed:
    'The operation state changed. Load current data and review the operation again.',
  admin_operation_invalid: 'Invalid operation data. Check the entered values.',
  admin_projection_limit:
    'The result is too large to display. Narrow the scope.',
  admin_module_unknown: 'This module is no longer available.',
  admin_operation_failed:
    'The operation could not complete. Check its current state before trying again.',
  csrf: 'Form security expired. Renew it and submit again.',
  authentication_required: 'Your session expired. Sign in again.',
  step_up_required: 'Verify your identity to perform the operation.',
  preference_application_unknown:
    'Preferences for this application are unavailable.',
  email_bypass_disabled: 'Administrator email verification is disabled.',
  session_selection_invalid:
    'Invalid session selection. Select current sessions.',
  session_selection_limit: 'Too many sessions selected. Reduce the selection.',
  user_selection_invalid: 'Invalid user selection. Select current accounts.',
  user_selection_limit: 'Too many users selected. Reduce the selection.',
  user_preview_stale:
    'Selected accounts changed. Review the operation effects again.',
  session_preview_stale:
    'Selected sessions changed. Review the operation effects again.',
};
