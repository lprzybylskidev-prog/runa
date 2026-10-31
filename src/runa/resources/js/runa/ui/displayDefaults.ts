import { readonly, shallowRef } from 'vue';
export interface ApplicationDisplayDefaults {
  readonly locale: 'pl' | 'en';
  readonly theme: 'system' | 'light' | 'dark';
  readonly timezone: string;
}
const defaults = shallowRef<ApplicationDisplayDefaults>({
  locale: 'pl',
  theme: 'system',
  timezone: 'Europe/Warsaw',
});
export function useApplicationDisplayDefaults() {
  return readonly(defaults);
}
/** Internal hydration; never writes an account or browser preference. */
export function applyApplicationDisplayDefaults(
  value?: ApplicationDisplayDefaults,
) {
  defaults.value = value ?? {
    locale: 'pl',
    theme: 'system',
    timezone: 'Europe/Warsaw',
  };
}
export function formatApplicationInstant(
  value: string | number | Date,
  locale: 'pl' | 'en',
): string {
  const date = new Date(value);
  return Number.isNaN(date.getTime())
    ? '—'
    : date.toLocaleString(locale === 'pl' ? 'pl-PL' : 'en-GB', {
        timeZone: defaults.value.timezone,
      });
}
