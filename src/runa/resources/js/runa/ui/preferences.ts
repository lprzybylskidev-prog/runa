export type { Locale } from '../../locales/messages';
export type { Theme } from '../../composables/useTheme';

/** Optional browser-local persistence. Implementations must tolerate denied storage. */
export interface PreferenceStorage {
  read(key: string): string | null;
  write(key: string, value: string): void;
  remove(key: string): void;
}

/** Business screens scope their settings by stable application/module/view keys. */
export interface PreferenceScope {
  readonly applicationId: string;
  readonly moduleKey: string;
  readonly viewKey: string;
}
