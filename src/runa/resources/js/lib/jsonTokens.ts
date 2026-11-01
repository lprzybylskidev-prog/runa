interface JsonToken {
  text: string;
  kind: 'key' | 'string' | 'number' | 'literal' | 'punctuation' | 'plain';
}
/** Presentation-only tokens, retaining every source character, including partial JSON. */
export function jsonTokens(text: string): JsonToken[] {
  const result: JsonToken[] = [];
  const pattern =
    /"(?:\\[\s\S]|[^"\\])*(?:"|$)|-?(?:0|[1-9]\d*)(?:\.\d+)?(?:[eE][+-]?\d+)?|\b(?:true|false|null)\b|[{}[\],:]/g;
  let offset = 0;
  for (const match of text.matchAll(pattern)) {
    if (match.index > offset)
      result.push({ text: text.slice(offset, match.index), kind: 'plain' });
    const value = match[0];
    const end = match.index + value.length;
    const kind = value.startsWith('"')
      ? /^\s*:/.test(text.slice(end))
        ? 'key'
        : 'string'
      : /^-?\d/.test(value)
        ? 'number'
        : /^(true|false|null)$/.test(value)
          ? 'literal'
          : 'punctuation';
    result.push({ text: value, kind });
    offset = end;
  }
  if (offset < text.length)
    result.push({ text: text.slice(offset), kind: 'plain' });
  return result;
}
