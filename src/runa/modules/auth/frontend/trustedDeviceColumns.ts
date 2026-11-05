import { formatApplicationInstant, instantTimestamp } from '@/runa/ui';
import type { TableColumn } from '@/runa/ui';

type DeviceKey =
  | 'browser'
  | 'platform'
  | 'current'
  | 'created_at'
  | 'last_used_at'
  | 'expires_at';

/** Shared own/Admin presentation; source metadata never identifies an authority. */
export function trustedDeviceColumns<Row>(
  locale: 'pl' | 'en',
  read: (row: Row, key: DeviceKey) => unknown,
): TableColumn<Row>[] {
  const text = (pl: string, en: string) => (locale === 'pl' ? pl : en);
  const date = (row: Row, key: DeviceKey) => {
    const value = read(row, key);
    return typeof value === 'string'
      ? formatApplicationInstant(value, locale)
      : '—';
  };
  const time = (row: Row, key: DeviceKey) => {
    const value = read(row, key);
    return typeof value === 'string' ? instantTimestamp(value) : 0;
  };
  return [
    {
      key: 'device',
      label: text('Urządzenie', 'Device'),
      value: (row) => {
        const browser = read(row, 'browser'),
          platform = read(row, 'platform');
        return [
          typeof browser === 'string'
            ? browser
            : text('Nieznana przeglądarka', 'Unknown browser'),
          typeof platform === 'string'
            ? platform
            : text('Nieznany system', 'Unknown system'),
        ].join(' · ');
      },
    },
    {
      key: 'current',
      label: text('Bieżące urządzenie', 'Current device'),
      value: (row) =>
        read(row, 'current') === true
          ? text('Ta przeglądarka', 'This browser')
          : '—',
    },
    ...(['created_at', 'last_used_at', 'expires_at'] as const).map((key) => ({
      key,
      label: {
        created_at: text('Utworzono', 'Created at'),
        last_used_at: text('Ostatnie użycie', 'Last used'),
        expires_at: text('Wygasa', 'Expires at'),
      }[key],
      value: (row: Row) => date(row, key),
      compare: (a: Row, b: Row) => time(a, key) - time(b, key),
    })),
  ];
}
