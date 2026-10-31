import { computed, inject, onScopeDispose, provide, shallowRef } from 'vue';
import type { InjectionKey } from 'vue';
import type { NavigationEntry, RouteContext } from '../runa/ui/navigation';
interface PageNavigationRegistration {
  entries: () => readonly NavigationEntry[];
  current: () => RouteContext | undefined;
  navigate: (href: string, event: MouseEvent) => void;
}
const hostKey: InjectionKey<
  (registration: PageNavigationRegistration) => () => void
> = Symbol('page-navigation');
/** One page publisher inside one mounted layout. No session, router or global state. */
export function usePageNavigationHost() {
  const page = shallowRef<PageNavigationRegistration>();
  provide(hostKey, (registration) => {
    page.value = registration;
    return () => {
      if (page.value === registration) page.value = undefined;
    };
  });
  return {
    entries: computed(() => page.value?.entries() ?? []),
    current: computed(() => page.value?.current()),
    navigate(href: string, event: MouseEvent): boolean {
      if (
        event.defaultPrevented ||
        event.button !== 0 ||
        event.metaKey ||
        event.ctrlKey ||
        event.shiftKey ||
        event.altKey
      )
        return false;
      function contains(entries: readonly NavigationEntry[]): boolean {
        return entries.some(
          (entry) =>
            entry.availability === 'available' &&
            (entry.href === href || contains(entry.children ?? [])),
        );
      }
      if (!page.value || !contains(page.value.entries())) return false;
      page.value.navigate(href, event);
      event.preventDefault();
      return true;
    },
  };
}
/** Internal registration adapter; consumers use PageNavigation. */
export function registerPageNavigation(
  registration: PageNavigationRegistration,
): boolean {
  const register = inject(hostKey, undefined);
  if (!register) return false;
  onScopeDispose(register(registration));
  return true;
}
