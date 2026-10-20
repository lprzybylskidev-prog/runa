import { computed, inject } from 'vue';
import type { ComputedRef, InjectionKey } from 'vue';
import version from '../../../../../../VERSION?raw';
import {
  id,
  name,
  version as applicationVersion,
} from '../../../../../app/application.json';

export interface ApplicationIdentity {
  readonly applicationId: string;
  readonly applicationName: string;
  readonly applicationVersion: string;
  readonly platformVersion: string;
  readonly logo?: { readonly src: string; readonly accessibleName: string };
}

export interface FooterOptions {
  readonly visible?: boolean;
  readonly application?: boolean;
  readonly platform?: boolean;
  readonly copyright?: boolean;
  readonly repository?: boolean;
  readonly copyrightText?: string;
  readonly repositoryUrl?: string;
  readonly repositoryLabel?: string;
}

export const defaultIdentity: Readonly<ApplicationIdentity> =
  /* @__PURE__ */ Object.freeze({
    applicationId: id,
    applicationName: name,
    applicationVersion: applicationVersion,
    platformVersion: version.trim(),
  });

/** Only same-origin absolute asset paths and public HTTPS URLs are supported. */
export function safeBrandUrl(value: string): boolean {
  if (/[^\S ]|[\\<>"']/u.test(value)) return false;
  if (value.startsWith('/') && !value.startsWith('//')) return true;
  try {
    const url = new URL(value);
    return url.protocol === 'https:' && !url.username && !url.password;
  } catch {
    return false;
  }
}

export function resolveIdentity(
  overrides: Partial<ApplicationIdentity> = {},
): ApplicationIdentity {
  const text = (value: string | undefined, fallback: string) =>
    value?.trim() || fallback;
  const name = text(overrides.applicationName, defaultIdentity.applicationName);
  const logo = overrides.logo;
  return {
    applicationId: text(overrides.applicationId, defaultIdentity.applicationId),
    applicationName: name,
    applicationVersion: text(
      overrides.applicationVersion,
      defaultIdentity.applicationVersion,
    ),
    platformVersion: text(
      overrides.platformVersion,
      defaultIdentity.platformVersion,
    ),
    ...(logo && safeBrandUrl(logo.src)
      ? {
          logo: {
            src: logo.src,
            accessibleName: text(logo.accessibleName, name),
          },
        }
      : {}),
  };
}

export const identityKey: InjectionKey<ComputedRef<ApplicationIdentity>> =
  Symbol('runa.identity');

/** Works without installation; an optional provider scopes overrides to its subtree. */
export function useIdentity(): ComputedRef<ApplicationIdentity> {
  return inject(
    identityKey,
    computed(() => defaultIdentity),
  );
}
