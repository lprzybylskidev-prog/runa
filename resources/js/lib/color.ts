export interface HsvColor {
  h: number;
  s: number;
  v: number;
}
export function hexToHsv(hex: string): HsvColor {
  if (!/^#[\da-f]{6}$/i.test(hex))
    throw new TypeError('Expected a six-digit hexadecimal color.');
  const [r, g, b] = [1, 3, 5].map(
    (i) => parseInt(hex.slice(i, i + 2), 16) / 255,
  ) as [number, number, number];
  const max = Math.max(r, g, b),
    min = Math.min(r, g, b),
    delta = max - min;
  const h =
    delta === 0
      ? 0
      : max === r
        ? ((g - b) / delta) % 6
        : max === g
          ? (b - r) / delta + 2
          : (r - g) / delta + 4;
  return {
    h: (h * 60 + 360) % 360,
    s: max === 0 ? 0 : (delta / max) * 100,
    v: max * 100,
  };
}
export function hsvToHex({ h, s, v }: HsvColor): string {
  const sat = Math.max(0, Math.min(100, s)) / 100,
    val = Math.max(0, Math.min(100, v)) / 100;
  const hue = (((h % 360) + 360) % 360) / 60,
    c = val * sat,
    x = c * (1 - Math.abs((hue % 2) - 1)),
    m = val - c;
  const rgb =
    hue < 1
      ? [c, x, 0]
      : hue < 2
        ? [x, c, 0]
        : hue < 3
          ? [0, c, x]
          : hue < 4
            ? [0, x, c]
            : hue < 5
              ? [x, 0, c]
              : [c, 0, x];
  return (
    '#' +
    rgb
      .map((channel) =>
        Math.round((channel + m) * 255)
          .toString(16)
          .padStart(2, '0'),
      )
      .join('')
  );
}
