import { onScopeDispose, ref, watch } from 'vue';
import { request } from '@/runa/http';
import type { ApplicationSessionController } from './contracts/controllers';

/** Optional navigation advertisement, tied to the shell's existing session owner. */
export function useAdminEntry(session: ApplicationSessionController) {
  const available = ref(false);
  const settled = ref(false);
  let epoch = 0;
  let identity: string | undefined;
  let abort: AbortController | undefined;
  const stop = watch(
    () =>
      JSON.stringify([
        session.session.value?.public_id,
        session.session.value?.session.public_id,
        session.session.value?.restrictions,
        session.authorityRevision.value,
        session.paused.value,
        session.loading.value,
        session.pending.value,
      ]),
    () => {
      const own = ++epoch;
      abort?.abort();
      const previousIdentity = identity;
      identity = undefined;
      if (
        !session.session.value ||
        session.session.value.restrictions.length ||
        session.paused.value ||
        session.loading.value ||
        session.pending.value
      ) {
        available.value = false;
        settled.value = false;
        return;
      }
      identity = JSON.stringify([
        session.session.value.public_id,
        session.session.value.session.public_id,
      ]);
      if (identity !== previousIdentity) {
        available.value = false;
        settled.value = false;
      }
      abort = new AbortController();
      void request('/admin/entry', {
        signal: AbortSignal.any([abort.signal, AbortSignal.timeout(5000)]),
      })
        .then((response) => {
          if (own !== epoch) return;
          const data =
            response !== null &&
            typeof response === 'object' &&
            'data' in response
              ? response.data
              : undefined;
          available.value =
            data !== null &&
            typeof data === 'object' &&
            Object.keys(data).length === 1 &&
            'available' in data &&
            data.available === true;
        })
        .catch(() => {
          if (own === epoch) available.value = false;
        })
        .finally(() => {
          if (own === epoch) settled.value = true;
        });
    },
    { immediate: true, flush: 'sync' },
  );
  onScopeDispose(() => {
    ++epoch;
    abort?.abort();
    stop();
  });
  return { available, settled };
}
