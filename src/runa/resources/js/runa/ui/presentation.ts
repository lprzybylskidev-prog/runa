import { request } from '../../lib/http';
import { safeBrandUrl } from './identity';
export interface ApplicationPresentation {
  readonly name: string;
  readonly logo: {
    readonly src: string;
    readonly accessibleName: string;
  } | null;
  readonly revision: number;
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
    return {
      name: data.name,
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
