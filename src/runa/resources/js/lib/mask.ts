import type { MaskResult } from '../runa/ui/advancedForms';
type Token = { literal: string } | { accepts: RegExp };
function tokens(pattern: string): Token[] {
  const result: Token[] = [];
  let escape = false;
  for (const character of pattern) {
    if (escape) {
      result.push({ literal: character });
      escape = false;
    } else if (character === '\\') escape = true;
    else if (character === '#') result.push({ accepts: /[0-9]/u });
    else if (character === 'A') result.push({ accepts: /\p{L}/u });
    else if (character === '*') result.push({ accepts: /[\p{L}\p{N}]/u });
    else result.push({ literal: character });
  }
  if (escape) throw new TypeError('A mask cannot end with an escape.');
  if (!result.some((token) => 'accepts' in token))
    throw new TypeError('A mask requires an editable token.');
  return result;
}
/** Formatting is not domain validation; incomplete raw data is still emitted. */
function applyMask(
  value: string | null,
  pattern: string,
  formattedInput = false,
): MaskResult {
  const parts = tokens(pattern);
  const input = Array.from(value ?? '');
  let cursor = 0;
  let raw = '';
  let formatted = '';
  let literals = '';
  let count = 0;
  const boundaries: number[] = [];
  for (const token of parts) {
    if ('literal' in token) {
      if (formattedInput && input[cursor] === token.literal) cursor++;
      literals += token.literal;
      continue;
    }
    let character: string | undefined;
    while (cursor < input.length) {
      const candidate = input[cursor++]!;
      if (token.accepts.test(candidate)) {
        character = candidate;
        break;
      }
    }
    if (!character) break;
    formatted += literals;
    literals = '';
    if (!boundaries.length) boundaries.push(formatted.length);
    raw += character;
    formatted += character;
    boundaries.push(formatted.length);
    count++;
  }
  const capacity = parts.filter((token) => 'accepts' in token).length;
  if (count === capacity && count) formatted += literals;
  return {
    raw: raw || null,
    formatted,
    complete: count === capacity,
    boundaries: boundaries.length ? boundaries : [0],
  };
}

/** Format a raw model. Use parseMaskedInput for already formatted DOM text. */
export function formatMask(value: string | null, pattern: string): MaskResult {
  return applyMask(value, pattern);
}
export function parseMaskedInput(value: string, pattern: string): MaskResult {
  return applyMask(value, pattern, true);
}
