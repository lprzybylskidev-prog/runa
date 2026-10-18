import { onScopeDispose, shallowRef } from 'vue';
import type { PageDescriptor } from '../runa/ui/navigation';

/** Exact registered paths only. Query/hash are preserved but never select a module. */
export function createPageResolver(
  pages: readonly PageDescriptor[],
  origin = window.location.origin,
) {
  const paths = new Map<string, PageDescriptor>();
  for (const page of pages) {
    const url = new URL(page.href, origin);
    if (
      url.origin !== origin ||
      !page.href.startsWith('/') ||
      page.href.startsWith('//') ||
      url.search ||
      url.hash ||
      paths.has(url.pathname) ||
      !page.serverRouteName
    )
      throw new TypeError(
        'Pages require unique same-origin paths and named server routes.',
      );
    paths.set(url.pathname, page);
  }
  function resolve(href: string): PageDescriptor | undefined {
    try {
      const url = new URL(href, origin);
      return url.origin === origin ? paths.get(url.pathname) : undefined;
    } catch {
      return undefined;
    }
  }
  return { resolve };
}

export function usePageResolver(pages: readonly PageDescriptor[]) {
  const resolver = createPageResolver(pages);
  const current = shallowRef(resolver.resolve(window.location.href));
  function refresh() {
    current.value = resolver.resolve(window.location.href);
  }
  window.addEventListener('popstate', refresh);
  onScopeDispose(() => window.removeEventListener('popstate', refresh));
  function navigate(href: string, event?: MouseEvent): boolean {
    if (
      event &&
      (event.defaultPrevented ||
        event.button !== 0 ||
        event.metaKey ||
        event.ctrlKey ||
        event.shiftKey ||
        event.altKey)
    )
      return false;
    if (!resolver.resolve(href)) return false;
    event?.preventDefault();
    window.history.pushState(null, '', href);
    refresh();
    return true;
  }
  return { current, navigate, resolve: resolver.resolve };
}
