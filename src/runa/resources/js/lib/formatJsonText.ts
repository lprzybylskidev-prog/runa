/** Format complete JSON without reserializing numbers, strings or duplicate keys. */
export function formatJsonText(text: string): string {
  try {
    JSON.parse(text);
  } catch {
    // A bounded continuation may start or end inside a JSON token.
    return text;
  }
  let output = '',
    depth = 0,
    quoted = false,
    escaped = false;
  const newline = () => '\n' + '  '.repeat(depth);
  for (let index = 0; index < text.length; index++) {
    const char = text[index]!;
    if (quoted) {
      output += char;
      if (escaped) escaped = false;
      else if (char === '\\') escaped = true;
      else if (char === '"') quoted = false;
    } else if (char === '"') {
      quoted = true;
      output += char;
    } else if (char === '{' || char === '[') {
      const next = text.slice(index + 1).search(/\S/);
      const closing = char === '{' ? '}' : ']';
      if (next >= 0 && text[index + 1 + next] === closing) {
        output += char + closing;
        index += next + 1;
      } else {
        depth++;
        output += char + newline();
      }
    } else if (char === '}' || char === ']') {
      depth--;
      output += newline() + char;
    } else if (char === ',') output += char + newline();
    else if (char === ':') output += ': ';
    else if (!/\s/.test(char)) output += char;
  }
  return output;
}
