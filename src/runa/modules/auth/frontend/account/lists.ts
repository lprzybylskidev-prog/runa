import { ref, shallowRef, onScopeDispose } from 'vue';
import type { Ref } from 'vue';
import { RequestCancelledError } from '../../../../resources/js/lib/http';
import { accountRequest, pageResponse } from '../account';
import type { AccountRow } from '../account';
import { data, authPost } from '../entry';
import type { SecurityOptions } from '../entry';
import { historyDetailResponse } from '../historyDetails';
import type { AccountHistoryDetail } from '../historyDetails';
import type { AccountTab } from './types';
import type { AccountLifetime, AccountPerform, AccountText } from './context';

export function createAccountLists(
  lifetime: AccountLifetime,
  state: {
    tab: Ref<AccountTab>;
    security: Ref<SecurityOptions | undefined>;
    notice: Ref<string>;
  },
  perform: AccountPerform,
  text: AccountText,
) {
  const { tab, security, notice } = state;
  const pageLoading = ref(false),
    trustedLoading = ref(false);
  const rows = ref<AccountRow[]>([]),
    cursor = ref<string | null>(null);
  const trustedRows = ref<AccountRow[]>([]),
    trustedCursor = ref<string | null>(null);
  let readEpoch = 0,
    trustedReadEpoch = 0;
  async function readPage(append = false) {
    const own = ++readEpoch,
      currentTab = tab.value;
    pageLoading.value = true;
    try {
      const path =
        currentTab === 'sessions'
          ? 'sessions'
          : currentTab === 'trust'
            ? 'trusted-devices'
            : 'profile/history';
      const input: Record<string, string> = {};
      if (currentTab.endsWith('History'))
        input.kind = currentTab === 'loginHistory' ? 'login' : 'account';
      if (append && cursor.value) input.before = cursor.value;
      const response = await accountRequest(
        path,
        'GET',
        input,
        lifetime.signal,
      );
      const page = currentTab.endsWith('History')
        ? (() => {
            const p = data(response);
            return pageResponse({
              data: p.entries,
              meta: { next_cursor: p.next },
            });
          })()
        : pageResponse(response);
      if (lifetime.active && own === readEpoch && currentTab === tab.value) {
        rows.value = append ? [...rows.value, ...page.rows] : page.rows;
        cursor.value = page.cursor;
      }
    } finally {
      if (own === readEpoch) pageLoading.value = false;
    }
  }
  const historyDetail = shallowRef<AccountHistoryDetail>();
  const historyDetailOpen = ref(false),
    historyDetailLoading = ref(false),
    historyDetailFailed = ref(false);
  let historyDetailId = '',
    historyDetailEpoch = 0;
  let historyDetailAbort: AbortController | undefined;
  function closeHistoryDetail() {
    historyDetailEpoch++;
    historyDetailAbort?.abort();
    historyDetailOpen.value = false;
    historyDetailLoading.value = false;
    historyDetail.value = undefined;
  }
  async function openHistoryDetail(publicId: string) {
    historyDetailAbort?.abort();
    const own = ++historyDetailEpoch;
    historyDetailId = publicId;
    historyDetailAbort = new AbortController();
    historyDetailOpen.value = true;
    historyDetailLoading.value = true;
    historyDetailFailed.value = false;
    historyDetail.value = undefined;
    try {
      const response = await accountRequest(
        'profile/history/detail',
        'GET',
        { event: publicId },
        AbortSignal.any([lifetime.signal, historyDetailAbort.signal]),
      );
      if (lifetime.active && own === historyDetailEpoch)
        historyDetail.value = historyDetailResponse(response, publicId);
    } catch (error) {
      if (
        lifetime.active &&
        own === historyDetailEpoch &&
        !historyDetailAbort.signal.aborted &&
        !(error instanceof RequestCancelledError)
      )
        historyDetailFailed.value = true;
    } finally {
      if (lifetime.active && own === historyDetailEpoch)
        historyDetailLoading.value = false;
    }
  }
  function retryHistoryDetail() {
    return openHistoryDetail(historyDetailId);
  }
  async function readTrustedDevices(append = false) {
    const own = ++trustedReadEpoch;
    trustedLoading.value = true;
    try {
      const input =
        append && trustedCursor.value ? { before: trustedCursor.value } : {};
      const page = pageResponse(
        await accountRequest('trusted-devices', 'GET', input, lifetime.signal),
      );
      if (lifetime.active && own === trustedReadEpoch) {
        trustedRows.value = append
          ? [...trustedRows.value, ...page.rows]
          : page.rows;
        trustedCursor.value = page.cursor;
      }
    } finally {
      if (own === trustedReadEpoch) trustedLoading.value = false;
    }
  }
  async function loadTrustedDevices(append = false) {
    if (!security.value?.trusted_devices_enabled) return false;
    return (await perform(() => readTrustedDevices(append), false)) === true;
  }
  async function revokeTrustedDevice(publicId: string) {
    if (
      !security.value?.trusted_devices_enabled ||
      !trustedRows.value.some((row) => row.public_id === publicId)
    )
      return false;
    return (
      (await perform(async () => {
        await authPost(
          'trusted-devices/revoke',
          { public_id: publicId },
          lifetime.signal,
        );
        if (!lifetime.active) return;
        trustedRows.value = trustedRows.value.filter(
          (row) => row.public_id !== publicId,
        );
        notice.value = text(
          'Zaufanie urządzeniu zostało unieważnione.',
          'Device trust revoked.',
        );
      }, false)) === true
    );
  }
  onScopeDispose(() => {
    closeHistoryDetail();
    readEpoch++;
    trustedReadEpoch++;
    trustedRows.value = [];
    trustedCursor.value = null;
  });
  return {
    pageLoading,
    trustedLoading,
    rows,
    cursor,
    trustedRows,
    trustedCursor,
    readPage,
    readTrustedDevices,
    loadTrustedDevices,
    revokeTrustedDevice,
    historyDetail,
    historyDetailOpen,
    historyDetailLoading,
    historyDetailFailed,
    openHistoryDetail,
    closeHistoryDetail,
    retryHistoryDetail,
  };
}
