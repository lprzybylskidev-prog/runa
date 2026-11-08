import { adminText } from './localization';
import type { AdminFieldData, AdminFieldValue } from './contracts';

const publicId =
  /^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
function utcTimestamp(value: AdminFieldValue): boolean {
  if (typeof value !== 'string') return false;
  const parts =
    /^(\d{4})-(\d\d)-(\d\d)T(\d\d):(\d\d):(\d\d)(?:\.\d+)?(?:Z|\+00:00)$/.exec(
      value,
    );
  if (!parts) return false;
  const date = new Date(value);
  return (
    Number.isFinite(date.getTime()) &&
    [
      date.getUTCFullYear(),
      date.getUTCMonth() + 1,
      date.getUTCDate(),
      date.getUTCHours(),
      date.getUTCMinutes(),
      date.getUTCSeconds(),
    ].every((part, index) => part === Number(parts[index + 1]))
  );
}

/** Static UX hints only; owning write validation remains authoritative. */
function validateField(
  field: AdminFieldData,
  value: AdminFieldValue,
): readonly string[] {
  if (field.readonly) return [];
  if (field.type === 'json') {
    if (value === null)
      return field.required ? ['This field is required.'] : [];
    return typeof value === 'object' ? [] : ['Enter a valid value.'];
  }
  if (
    field.sensitive &&
    value !== null &&
    typeof value === 'object' &&
    !Array.isArray(value)
  ) {
    if (Object.keys(value).length === 1 && typeof value.set === 'boolean')
      return field.required && !value.set ? ['This field is required.'] : [];
    return ['Enter a valid value.'];
  }
  if (value === null || value === '' || (Array.isArray(value) && !value.length))
    return field.required ? ['This field is required.'] : [];
  if (field.sensitive) {
    if (typeof value !== 'string') return ['Enter a valid value.'];
    return field.max_length !== null && [...value].length > field.max_length
      ? [`Use at most ${field.max_length} characters.`]
      : [];
  }
  switch (field.type) {
    case 'text':
    case 'dictionary':
      if (typeof value !== 'string') return ['Enter a text value.'];
      if (field.max_length !== null && [...value].length > field.max_length)
        return [`Use at most ${field.max_length} characters.`];
      return field.choices.length && !field.choices.includes(value)
        ? ['Choose an available value.']
        : [];
    case 'integer':
      return typeof value === 'number' && Number.isSafeInteger(value)
        ? []
        : ['Enter a whole number within the supported range.'];
    case 'boolean':
      return typeof value === 'boolean' ? [] : ['Choose Yes or No.'];
    case 'uuid': {
      const valid = (item: AdminFieldValue) =>
        typeof item === 'string' && publicId.test(item);
      return (
        field.multiple
          ? Array.isArray(value) &&
            value.length <= 100 &&
            value.every(valid) &&
            new Set(value).size === value.length
          : valid(value)
      )
        ? []
        : ['Choose valid public UUIDs.'];
    }
    case 'datetime':
      return utcTimestamp(value) ? [] : ['Enter a valid date and time.'];
    default:
      return ['This field is currently unavailable.'];
  }
}

export function validateAdminField(
  field: AdminFieldData,
  value: AdminFieldValue,
  locale: 'pl' | 'en' = 'en',
): readonly string[] {
  return validateField(field, value).map((message) =>
    adminText(message, locale),
  );
}
