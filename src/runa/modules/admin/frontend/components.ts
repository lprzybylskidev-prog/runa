import { defineAsyncComponent, markRaw } from 'vue';
import type { Component } from 'vue';
import loaders, { builds } from 'virtual:runa-module-components';
import type { AdminComponent, AdminComponentRegistry } from './contracts';
import AdminUnavailable from './AdminUnavailable.vue';
import AdminLoading from './AdminLoading.vue';
const cache = new Map<string, Component>();
/** A non-null identity never falls back to a default when its build is unavailable. */
export function resolveAdminComponent(
  identity: AdminComponent | null,
  components: AdminComponentRegistry = {},
): { component: Component | undefined; available: boolean } {
  if (identity === null) return { component: undefined, available: true };
  const unavailable = { component: AdminUnavailable, available: false };
  const evidence = builds.owners[identity.owner];
  if (
    !identity.build_id ||
    !evidence ||
    evidence.build_id !== identity.build_id ||
    evidence.manifest_hash !== identity.manifest_hash ||
    !evidence.components.includes(identity.key)
  )
    return unavailable;
  if (identity.owner === 'app') {
    const entry = Object.hasOwn(components, identity.key)
      ? components[identity.key]
      : undefined;
    if (
      !/^app\.[a-z][a-z0-9_]*$/.test(identity.key) ||
      identity.manifest_hash !== null ||
      entry?.buildId !== identity.build_id
    )
      return unavailable;
    return { component: entry.component, available: true };
  }
  if (
    !identity.key.startsWith(identity.owner + '.') ||
    !/^(app|runa)\.[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/.test(identity.key)
  )
    return unavailable;
  const loader = Object.hasOwn(loaders, identity.key)
    ? loaders[identity.key]
    : undefined;
  if (!loader || loader.manifestHash !== identity.manifest_hash)
    return unavailable;
  const cacheKey = identity.key + ':' + identity.build_id;
  let component = cache.get(cacheKey);
  if (!component) {
    component = markRaw(
      defineAsyncComponent({
        loader: () => loader.load().then((m) => m.default),
        loadingComponent: AdminLoading,
        errorComponent: AdminUnavailable,
        timeout: 15000,
      }),
    );
    cache.set(cacheKey, component);
  }
  return { component, available: true };
}
