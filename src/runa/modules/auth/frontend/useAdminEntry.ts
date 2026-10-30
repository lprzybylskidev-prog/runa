import { onScopeDispose, ref, watch } from 'vue';
import { request } from '@/runa/http';
import type { ApplicationSessionController } from './contracts/controllers';

/** Optional navigation advertisement, tied to the shell's existing session owner. */
export function useAdminEntry(session: ApplicationSessionController) {
  const available = ref(false);
  let epoch = 0;
  let abort: AbortController | undefined;
  const stop = watch(
    () =>
      [
        session.session.value?.session.public_id,
        session.authorityRevision.value,
        session.paused.value,
        session.loading.value,
        session.pending.value,
      ] as const,
    () => {
      const own = ++epoch;
      abort?.abort();
      available.value = false;
      if (
        !session.session.value ||
        session.session.value.restrictions.length ||
        session.paused.value ||
        session.loading.value ||
        session.pending.value
      )
        return;
      abort = new AbortController();
      void request('/admin/entry', { signal: abort.signal })
        .then((response) => {
          if (
            own !== epoch ||
            response === null ||
            typeof response !== 'object' ||
            !('data' in response)
          )
            return;
          const data = response.data;
          available.value =
            data !== null &&
            typeof data === 'object' &&
            Object.keys(data).length === 1 &&
            'available' in data &&
            data.available === true;
        })
        .catch(() => {
          /* Missing authority or an unavailable optional link stays hidden. */
        });
    },
    { immediate: true, flush: 'sync' },
  );
  onScopeDispose(() => {
    ++epoch;
    abort?.abort();
    stop();
  });
  return available;
}
