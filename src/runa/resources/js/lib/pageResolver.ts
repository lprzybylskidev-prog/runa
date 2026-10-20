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

export function usePageResolver(
  pages: readonly PageDescriptor[],
  options: { beforeLeave?: (href: string) => boolean | Promise<boolean> } = {},
) {
  const resolver = createPageResolver(pages);
  const current = shallowRef(resolver.resolve(window.location.href));
  function refresh() {
    current.value = resolver.resolve(window.location.href);
  }
  const marker = '__runa_page_position';
  let position = Number.isInteger(window.history.state?.[marker])
    ? Number(window.history.state[marker])
    : 0;
  if (options.beforeLeave)
    window.history.replaceState(
      { ...window.history.state, [marker]: position },
      '',
      window.location.href,
    );
  let generation = 0;
  let restoring = false;
  let disposed = false;
  async function permitted(href: string) {
    try {
      return Boolean(await options.beforeLeave?.(href));
    } catch {
      return false;
    }
  }
  async function pop(event: globalThis.PopStateEvent) {
    if (!options.beforeLeave) {
      refresh();
      return;
    }
    if (restoring) {
      restoring = false;
      refresh();
      return;
    }
    const target = event.state?.[marker];
    // Entries made outside this resolver require a document navigation adapter.
    if (!Number.isInteger(target)) {
      refresh();
      return;
    }
    const request = ++generation;
    const href = window.location.href;
    if (await permitted(href)) {
      if (!disposed && request === generation) {
        position = target;
        refresh();
      }
    } else if (!disposed && request === generation) {
      const delta = position - target;
      if (delta) {
        restoring = true;
        window.history.go(delta);
      }
    }
  }
  window.addEventListener('popstate', pop);
  onScopeDispose(() => {
    disposed = true;
    generation++;
    window.removeEventListener('popstate', pop);
  });
  function navigate(
    href: string,
    event?: MouseEvent,
  ): boolean | Promise<boolean> {
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
    function commit() {
      window.history.pushState(
        options.beforeLeave ? { [marker]: ++position } : null,
        '',
        href,
      );
      refresh();
      return true;
    }
    if (!options.beforeLeave) return commit();
    const request = ++generation;
    return permitted(href).then((allowed) =>
      allowed && !disposed && request === generation ? commit() : false,
    );
  }
  return { current, navigate, resolve: resolver.resolve };
}
