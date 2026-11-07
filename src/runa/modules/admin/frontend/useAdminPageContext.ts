import { computed, onScopeDispose } from 'vue';
import type { AdminPageController } from './contracts';
import { useAdminInitialReadiness } from './initialReadiness';
import { pageOwners } from './pageContext';
import { adminPresentationIdentity } from './useAdmin';
import { navigationGuards, type NavigationGuard } from './runtime';

/** Native lifecycle for custom owning-HTTP pages; no second session or private App import. */
export function useAdminPageContext(
  page: AdminPageController,
  options: {
    beforeLeave?: () => Promise<boolean>;
    dirty?: () => boolean;
    settled?: () => boolean;
  } = {},
) {
  const admin = pageOwners.get(page);
  useAdminInitialReadiness(options.settled);
  const identity = computed(() => {
    const session = admin
      ? adminPresentationIdentity(admin.session)
      : undefined;
    return session &&
      admin?.state.value === 'ready' &&
      page.state.value === 'ready' &&
      page.page.value
      ? JSON.stringify([session, page.page.value.owner, page.page.value.key])
      : undefined;
  });
  let alive = true;
  const leave: NavigationGuard = async (action) => {
    const admitted = identity.value;
    if (
      !alive ||
      !admitted ||
      (options.beforeLeave && !(await options.beforeLeave()))
    )
      return false;
    if (!alive || admitted !== identity.value) return false;
    await action();
    return true;
  };
  const guards = admin
    ? (navigationGuards.get(admin) ?? new Set<NavigationGuard>())
    : undefined;
  if (admin && guards) {
    guards.add(leave);
    navigationGuards.set(admin, guards);
  }
  function click(event: MouseEvent) {
    if (
      !options.beforeLeave ||
      !options.dirty?.() ||
      event.defaultPrevented ||
      event.button !== 0 ||
      event.metaKey ||
      event.ctrlKey ||
      event.shiftKey ||
      event.altKey
    )
      return;
    const anchor =
      event.target instanceof Element ? event.target.closest('a[href]') : null;
    if (
      !(anchor instanceof HTMLAnchorElement) ||
      (anchor.target && anchor.target !== '_self') ||
      anchor.hasAttribute('download') ||
      anchor.href === location.href
    )
      return;
    event.preventDefault();
    void leave(() => window.location.assign(anchor.href));
  }
  document.addEventListener('click', click, true);
  onScopeDispose(() => {
    alive = false;
    guards?.delete(leave);
    document.removeEventListener('click', click, true);
  });
  return { identity };
}
