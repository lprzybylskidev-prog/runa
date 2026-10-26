/** Only locally rendered enrollment SVG images are accepted; never contact a QR service. */
export function enrollmentQrCode(value: unknown): string {
  if (value === undefined) return '';
  if (
    typeof value !== 'string' ||
    value.length > 131072 ||
    !/^data:image\/svg\+xml;base64,[A-Za-z0-9+/]+={0,2}$/.test(value)
  )
    throw new TypeError('Invalid enrollment image.');
  return value;
}
