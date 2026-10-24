import { onMounted, onScopeDispose, ref, shallowRef } from 'vue';
import { useLocale } from '../../../resources/js/composables/useLocale';
import { authFailure, authGet, decodeOptions, decodeSession } from './entry';
import type { EntryOptions, EntrySession } from './entry';
export function useAuthWelcome() {
  const { locale } = useLocale();
  const text = (pl: string, en: string) => (locale.value === 'pl' ? pl : en);
  const options = shallowRef<EntryOptions>(),
    session = shallowRef<EntrySession>();
  const failed = ref(false),
    loading = ref(true);
  const abort = new AbortController();
  async function load() {
    loading.value = true;
    failed.value = false;
    try {
      const result = decodeOptions(
        await authGet('entry-options', abort.signal),
      );
      if (abort.signal.aborted) return;
      options.value = result;
      if (!result.public_welcome_enabled) {
        window.location.replace('/auth/login');
        return;
      }
      try {
        session.value = decodeSession(await authGet('session', abort.signal));
      } catch (error) {
        if (authFailure(error).code !== 'signed_out') throw error;
        session.value = undefined;
      }
    } catch {
      if (!abort.signal.aborted) failed.value = true;
    } finally {
      if (!abort.signal.aborted) loading.value = false;
    }
  }
  onMounted(load);
  onScopeDispose(() => abort.abort());

  return { text, options, session, failed, loading, load };
}
