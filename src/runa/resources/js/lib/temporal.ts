import type {
  DateRangeValue,
  TimeDisambiguation,
  ZonedDateTimeResult,
} from '../runa/ui/forms';
export function isDateOnly(value: string): boolean {
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
  if (!match) return false;
  const year = Number(match[1]),
    month = Number(match[2]),
    day = Number(match[3]);
  if (year < 1 || month < 1 || month > 12 || day < 1 || day > 31) return false;
  const date = new Date(0);
  date.setUTCFullYear(year, month - 1, day);
  date.setUTCHours(0, 0, 0, 0);
  return (
    date.getUTCFullYear() === year &&
    date.getUTCMonth() === month - 1 &&
    date.getUTCDate() === day
  );
}
export function isTimeOnly(value: string): boolean {
  return /^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d(?:\.\d{1,3})?)?$/.test(value);
}
export function validDateRange(value: DateRangeValue): boolean {
  return (
    (value.start === null || isDateOnly(value.start)) &&
    (value.end === null || isDateOnly(value.end)) &&
    (!value.start || !value.end || value.start <= value.end)
  );
}
function localEpoch(value: string): number | null {
  if (value.split('T').length !== 2) return null;
  const [date, time] = value.split('T');
  if (!date || !time || !isDateOnly(date) || !isTimeOnly(time)) return null;
  const [year, month, day] = date.split('-').map(Number);
  const [hour, minute, seconds = '0'] = time.split(':');
  const [second, fraction = ''] = seconds.split('.');
  const result = new Date(0);
  result.setUTCFullYear(year!, month! - 1, day!);
  result.setUTCHours(
    Number(hour),
    Number(minute),
    Number(second),
    Number(fraction.padEnd(3, '0')),
  );
  return result.getTime();
}
function formatter(timeZone: string) {
  return new Intl.DateTimeFormat('en-CA', {
    timeZone,
    calendar: 'iso8601',
    numberingSystem: 'latn',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hourCycle: 'h23',
  });
}
function zonedLocal(epoch: number, format: Intl.DateTimeFormat): string {
  const parts = Object.fromEntries(
    format.formatToParts(epoch).map((part) => [part.type, part.value]),
  );
  const ms = new Date(epoch).getUTCMilliseconds();
  return `${parts.year!.padStart(4, '0')}-${parts.month}-${parts.day}T${parts.hour}:${parts.minute}:${parts.second}${ms ? `.${String(ms).padStart(3, '0')}` : ''}`;
}
export function isInstant(value: string): boolean {
  const match =
    /^(\d{4}-\d{2}-\d{2})T((?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d(?:\.\d{1,3})?)?)(Z|[+-](?:[01]\d|2[0-3]):[0-5]\d)$/.exec(
      value,
    );
  return (
    !!match &&
    isDateOnly(match[1]!) &&
    isTimeOnly(match[2]!) &&
    Number.isFinite(Date.parse(value))
  );
}
export function toLocalDateTime(
  instant: string | null,
  timeZone: string,
): string {
  const format = formatter(timeZone);
  if (instant === null) return '';
  if (!isInstant(instant))
    throw new TypeError('An explicit-offset ISO instant is required.');
  return zonedLocal(Date.parse(instant), format);
}
/** Match real Intl timezone offsets; never silently normalize gaps or select an overlap. */
export function resolveLocalDateTime(
  value: string | null,
  timeZone: string,
  disambiguation: TimeDisambiguation = 'reject',
): ZonedDateTimeResult {
  let format: Intl.DateTimeFormat;
  try {
    format = formatter(timeZone);
  } catch {
    return { valid: false, reason: 'timezone', candidates: [] };
  }
  if (value === null || value === '') return { valid: true, value: null };
  const wall = localEpoch(value);
  if (wall === null) return { valid: false, reason: 'format', candidates: [] };
  const offsets = new Set<number>();
  // Both sides of nearby date-line/DST transitions, with second-accurate platform offsets.
  const anchor = Math.floor(wall / 86400000) * 86400000;
  for (let hour = -48; hour <= 48; hour++) {
    const sample = anchor + hour * 3600000;
    const local = localEpoch(zonedLocal(sample, format));
    if (local !== null) offsets.add(local - sample);
  }
  const candidates = [...offsets]
    .map((offset) => wall - offset)
    .filter((epoch) => localEpoch(zonedLocal(epoch, format)) === wall)
    .sort((a, b) => a - b)
    .map((epoch) => new Date(epoch).toISOString())
    .filter(isInstant);
  if (!candidates.length) return { valid: false, reason: 'gap', candidates };
  if (candidates.length > 1 && disambiguation === 'reject')
    return { valid: false, reason: 'ambiguous', candidates };
  return {
    valid: true,
    value: (disambiguation === 'later' ? candidates.at(-1) : candidates[0])!,
  };
}
export function formatDateOnly(
  value: string | null,
  locale: 'pl' | 'en',
): string {
  if (value === null) return '';
  if (!isDateOnly(value)) throw new TypeError('A date-only value is required.');
  const [year, month, day] = value.split('-');
  return locale === 'pl' ? `${day}.${month}.${year}` : value;
}
