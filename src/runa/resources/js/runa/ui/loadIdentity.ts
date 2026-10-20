import { request } from '../../lib/http';
import { defaultIdentity, resolveIdentity } from './identity';
import type { ApplicationIdentity } from './identity';

/** Use the runtime public projection; retain the built identity during temporary API failure. */
export async function loadApplicationIdentity(): Promise<ApplicationIdentity> {
  try {
    const payload = await request('/identity', {
      signal: AbortSignal.timeout(5000),
    });
    if (typeof payload !== 'object' || payload === null || !('data' in payload))
      throw new Error('Invalid identity response.');
    const value = payload.data;
    if (typeof value !== 'object' || value === null)
      throw new Error('Invalid identity response.');
    if (
      !('applicationId' in value) ||
      typeof value.applicationId !== 'string' ||
      !('applicationName' in value) ||
      typeof value.applicationName !== 'string' ||
      !('applicationVersion' in value) ||
      typeof value.applicationVersion !== 'string' ||
      !('platformVersion' in value) ||
      typeof value.platformVersion !== 'string'
    )
      throw new Error('Invalid identity response.');
    if (
      !value.applicationId.trim() ||
      !value.applicationName.trim() ||
      !value.applicationVersion.trim() ||
      !value.platformVersion.trim()
    )
      throw new Error('Invalid identity response.');
    const logo = 'logo' in value ? value.logo : undefined;
    if (
      logo !== undefined &&
      (typeof logo !== 'object' ||
        logo === null ||
        !('src' in logo) ||
        typeof logo.src !== 'string' ||
        !('accessibleName' in logo) ||
        typeof logo.accessibleName !== 'string')
    )
      throw new Error('Invalid identity response.');
    return resolveIdentity({
      applicationId: value.applicationId,
      applicationName: value.applicationName,
      applicationVersion: value.applicationVersion,
      platformVersion: value.platformVersion,
      ...(logo &&
      'src' in logo &&
      typeof logo.src === 'string' &&
      'accessibleName' in logo &&
      typeof logo.accessibleName === 'string'
        ? { logo: { src: logo.src, accessibleName: logo.accessibleName } }
        : {}),
    });
  } catch {
    return defaultIdentity;
  }
}
