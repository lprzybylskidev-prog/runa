import type { ApplicationDisplayDefaults } from './displayDefaults';
import { request } from '../../lib/http';
import { safeBrandUrl } from './identity';
export interface ApplicationPresentation {
  readonly name: string;
  readonly logo: {
    readonly src: string;
    readonly accessibleName: string;
  } | null;
  readonly revision: number;
  readonly favicon?: string | null;
  readonly defaults?: ApplicationDisplayDefaults;
}
/** Public branding only. Failure clears the override back to canonical source defaults. */
export async function loadApplicationPresentation(
  signal?: AbortSignal,
): Promise<ApplicationPresentation | undefined> {
  try {
    const payload = await request('/presentation', {
      signal: signal ?? AbortSignal.timeout(5000),
      cache: 'no-store',
    });
    if (!payload || typeof payload !== 'object' || !('data' in payload)) return;
    const data = payload.data;
    if (
      !data ||
      typeof data !== 'object' ||
      !('name' in data) ||
      typeof data.name !== 'string' ||
      !data.name.trim() ||
      !('revision' in data) ||
      typeof data.revision !== 'number' ||
      !Number.isSafeInteger(data.revision) ||
      data.revision < 0 ||
      !('logo' in data)
    )
      return;
    const logo = data.logo;
    if (
      logo !== null &&
      (!logo ||
        typeof logo !== 'object' ||
        !('src' in logo) ||
        typeof logo.src !== 'string' ||
        !safeBrandUrl(logo.src) ||
        !('accessibleName' in logo) ||
        typeof logo.accessibleName !== 'string')
    )
      return;
    const raw = 'defaults' in data ? data.defaults : undefined;
    let defaults: ApplicationDisplayDefaults | undefined;
    if (
      raw &&
      typeof raw === 'object' &&
      'locale' in raw &&
      'theme' in raw &&
      'timezone' in raw
    ) {
      if (
        (raw.locale !== 'pl' && raw.locale !== 'en') ||
        (raw.theme !== 'system' &&
          raw.theme !== 'light' &&
          raw.theme !== 'dark') ||
        typeof raw.timezone !== 'string'
      )
        return;
      try {
        new Intl.DateTimeFormat('en', { timeZone: raw.timezone });
      } catch {
        return;
      }
      defaults = {
        locale: raw.locale,
        theme: raw.theme,
        timezone: raw.timezone,
      };
    }
    const favicon = 'favicon' in data ? data.favicon : null;
    if (
      favicon !== null &&
      (typeof favicon !== 'string' || !safeBrandUrl(favicon))
    )
      return;
    return {
      name: data.name,
      favicon,
      ...(defaults ? { defaults } : {}),
      revision: data.revision,
      logo:
        logo === null
          ? null
          : {
              src: logo.src as string,
              accessibleName: logo.accessibleName as string,
            },
    };
  } catch {
    return;
  }
}
