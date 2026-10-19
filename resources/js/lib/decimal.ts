import type { DecimalOptions, DecimalResult } from '../runa/ui/forms';
/** No grouping, exponents, rounding, binary floating point or percent rescaling. */
export function parseDecimal(
  raw: string | null,
  options: DecimalOptions = {},
): DecimalResult {
  if (raw === null || raw.trim() === '') return { valid: true, value: null };
  let value = raw.trim();
  if (options.locale === 'pl') value = value.replace(',', '.');
  const pattern =
    options.kind === 'integer'
      ? /^[+-]?\d+$/
      : /^[+-]?(?:\d+(?:\.\d+)?|\.\d+)$/;
  if (!pattern.test(value)) return { valid: false, reason: 'format' };
  const negative = value.startsWith('-');
  value = value.replace(/^[+-]/, '');
  const [whole = '', fraction] = value.split('.');
  if (
    options.scale !== undefined &&
    (!Number.isInteger(options.scale) ||
      options.scale < 0 ||
      (fraction?.length ?? 0) > options.scale)
  )
    return { valid: false, reason: 'scale' };
  const normalized = (whole || '0').replace(/^0+(?=\d)/, '');
  return {
    valid: true,
    value: `${negative ? '-' : ''}${normalized}${fraction === undefined ? '' : `.${fraction}`}`,
  };
}
export function serializeDecimal(
  value: string | null,
  options: DecimalOptions = {},
): string | null {
  const result = parseDecimal(value, options);
  if (!result.valid) throw new TypeError('Invalid decimal value.');
  return result.value;
}
/** Exact display separator substitution, preserving all fractional digits. */
export function formatDecimal(
  value: string | null,
  locale: 'pl' | 'en',
): string {
  const canonical = serializeDecimal(value);
  return locale === 'pl'
    ? (canonical ?? '').replace('.', ',')
    : (canonical ?? '');
}
