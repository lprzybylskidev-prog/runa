import type { ComputedRef, InjectionKey } from 'vue';
import type { AdminCollectionAdapters } from './contracts';
/** Source-owned provider values, never paths/classes resolved from metadata. */
export const adminCollectionsKey: InjectionKey<
  ComputedRef<Readonly<Record<string, AdminCollectionAdapters>>>
> = Symbol('runa.admin.collections');
