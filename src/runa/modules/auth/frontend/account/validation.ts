import { computed } from 'vue';
import type { Ref } from 'vue';
import type { SecurityOptions, EntryOptions } from '../entry';
import type { AccountTask } from './types';
import type { AccountText } from './context';

export function createAccountValidation(state: {
  task: Ref<AccountTask>;
  proofMethods: Ref<readonly string[]>;
  security: Ref<SecurityOptions | undefined>;
  options: Ref<EntryOptions | undefined>;
  naming: Ref<string>;
  method: Ref<string>;
  fields: Record<string, string>;
  errors: Record<string, string>;
  has: (key: string) => boolean;
  cleanErrors: () => void;
  text: AccountText;
}) {
  const {
    task,
    proofMethods,
    security,
    options,
    naming,
    method,
    fields,
    errors,
    has,
    cleanErrors,
    text,
  } = state;
  const needsProof = computed(
    () =>
      proofMethods.value.length > 0 &&
      ([
        'totpBegin',
        'totpConfirm',
        'emailEnable',
        'disable',
        'recoveryRotate',
        'trustIssue',
      ].includes(task.value) ||
        (task.value === 'password' && security.value?.password_change_factor)),
  );
  const fieldNames = computed(() => [
    ...(task.value === 'details'
      ? [
          ...(has('own_name_editable')
            ? naming.value === 'single'
              ? ['name']
              : ['first_name', 'surname']
            : []),
          ...(has('own_login_editable') ? ['login'] : []),
        ]
      : []),
    ...(['email', 'secondaryAdd', 'secondaryChange'].includes(task.value)
      ? ['email']
      : []),
    ...([
      'password',
      'email',
      'totpBegin',
      'totpConfirm',
      'emailEnable',
      'disable',
      'recoveryRotate',
      'deactivate',
      'delete',
    ].includes(task.value)
      ? ['current_password']
      : []),
    ...(task.value === 'password' ? ['password', 'password_confirmation'] : []),
    ...(needsProof.value ? ['code'] : []),
    ...(task.value === 'totpConfirm' ? ['enrollment_code'] : []),
  ]);
  function emailError(value: string): string {
    return !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) ||
      value.length > 254 ||
      /[^\x21-\x7e]/.test(value)
      ? text('Podaj poprawny adres e-mail.', 'Enter a valid email address.')
      : '';
  }
  function validate() {
    cleanErrors();
    for (const key of fieldNames.value) {
      const value = fields[key] ?? '';
      if (
        !value.trim() &&
        !(key === 'login' && options.value?.identifier_mode !== 'login')
      )
        errors[key] = text('Wypełnij to pole.', 'Complete this field.');
      if (key === 'email' && emailError(value)) errors[key] = emailError(value);
      if (
        ['name', 'first_name', 'surname'].includes(key) &&
        (value.trim() !== value ||
          [...value].length > 160 ||
          /\p{C}/u.test(value))
      )
        errors[key] = text('Popraw dane.', 'Correct this value.');
      if (
        key === 'login' &&
        value &&
        !/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,63}$/.test(value)
      )
        errors[key] = text('Niepoprawny login.', 'Invalid login.');
      if (
        key === 'code' &&
        !(method.value === 'recovery' ? /^[0-9a-f]{32}$/ : /^[0-9]{6}$/).test(
          value,
        )
      )
        errors[key] = text('Niepoprawny kod.', 'Invalid code.');
      if (key === 'enrollment_code' && !/^[0-9]{6}$/.test(value))
        errors[key] = text('Wpisz 6 cyfr.', 'Enter 6 digits.');
    }
    if (task.value === 'password' && options.value) {
      const p = options.value.password,
        v = fields.password ?? '';
      if (
        [...v].length < p.min_length ||
        new TextEncoder().encode(v).length > p.max_bytes ||
        v.includes('\0') ||
        (p.mixed_case && (!/\p{Ll}/u.test(v) || !/\p{Lu}/u.test(v))) ||
        (p.numbers && !/\p{N}/u.test(v)) ||
        (p.symbols && !/[\p{S}\p{P}]/u.test(v))
      )
        errors.password = text(
          'Hasło nie spełnia podanych wymagań.',
          'Password does not meet the listed requirements.',
        );
      if (v !== fields.password_confirmation)
        errors.password_confirmation = text(
          'Hasła muszą być takie same.',
          'Passwords must match.',
        );
    }
    return Object.keys(errors).length === 0;
  }
  return { needsProof, fieldNames, validate, emailError };
}
