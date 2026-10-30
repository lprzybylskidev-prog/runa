import { computed, onScopeDispose, ref, shallowRef, watch } from 'vue';
import type { Ref } from 'vue';
import { request } from '@/runa/http';
import type {
  AdminController,
  AdminError,
  AdminLocation,
  AdminPageController,
  AdminPageData,
  AdminState,
} from './contracts';
import { adminPath, decodePage } from './decode';
import { adminFailure, refuseAdmin, sessionStamp } from './useAdmin';
export function useAdminPage(
  admin: AdminController,
  location: Ref<AdminLocation>,
): AdminPageController {
  const page = shallowRef<AdminPageData>(),
    error = shallowRef<AdminError>();
  const status = ref<AdminState>('loading');
  let epoch = 0,
    alive = true,
    abort = new AbortController();
  async function refresh() {
    const own = ++epoch,
      stamp = sessionStamp(admin.session),
      current = { ...location.value };
    const bootstrap = admin.bootstrap.value;
    abort.abort();
    abort = new AbortController();
    page.value = undefined;
    error.value = undefined;
    status.value = 'loading';
    if (admin.state.value !== 'ready' || !bootstrap) return;
    if (
      !bootstrap.owners.some(
        (o) =>
          o.id === current.owner && o.pages.some((p) => p.key === current.page),
      )
    ) {
      status.value = 'unavailable';
      return;
    }
    try {
      const result = decodePage(
        await request(adminPath(current), { signal: abort.signal }),
      );
      if (!alive || own !== epoch || stamp !== sessionStamp(admin.session))
        return;
      if (
        result.owner !== current.owner ||
        result.key !== current.page ||
        result.generation !== bootstrap.generation
      )
        throw new TypeError('Admin page identity changed.');
      page.value = result;
      status.value = 'ready';
    } catch (cause) {
      if (!alive || own !== epoch || stamp !== sessionStamp(admin.session))
        return;
      const failure = adminFailure(cause);
      error.value = failure.error;
      status.value = failure.state;
      if (failure.state === 'proof_required' || failure.state === 'forbidden')
        refuseAdmin(admin, cause);
    }
  }
  const stop = watch(
    () => [
      location.value.owner,
      location.value.page,
      admin.state.value,
      admin.bootstrap.value?.revision,
      sessionStamp(admin.session),
    ],
    () => {
      void refresh();
    },
    { immediate: true, flush: 'sync' },
  );
  onScopeDispose(() => {
    alive = false;
    ++epoch;
    abort.abort();
    page.value = undefined;
    stop();
  });
  return {
    state: computed(() =>
      admin.state.value === 'ready' ? status.value : admin.state.value,
    ),
    page,
    error,
    refresh,
  };
}
