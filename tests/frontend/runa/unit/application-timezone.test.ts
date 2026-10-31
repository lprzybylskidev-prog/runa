import { afterEach, expect, it, vi } from '@runa/testing/vitest';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import { nextTick } from 'vue';
import {
  DateInput,
  DateTimeInput,
  formatApplicationInstant,
  formatDateOnly,
  instantTimestamp,
} from '@/runa/ui';
import { applyApplicationDisplayDefaults } from '../../../../src/runa/resources/js/runa/ui/displayDefaults';
import AppCalendarPicker from '../../../../src/runa/resources/js/components/AppCalendarPicker.vue';
import AppFooter from '../../../../src/runa/resources/js/components/AppFooter.vue';
import AdminDashboard from '../../../../src/runa/modules/admin/frontend/AdminDashboard.vue';
import AuthHistoryDetail from '../../../../src/runa/modules/auth/frontend/AuthHistoryDetail.vue';
import type { AdminBootstrap } from '@/runa/admin';
import { activityResponse } from '../../../../src/runa/modules/auth/frontend/useApplicationSession';
enableAutoUnmount(afterEach);
afterEach(() => {
  applyApplicationDisplayDefaults();
  vi.useRealTimers();
});
function zone(timezone: string) {
  applyApplicationDisplayDefaults({ locale: 'en', theme: 'light', timezone });
}

it('application timezone never changes a session deadline parsed from a PostgreSQL UTC projection', () => {
  const id = '01900000-0000-7000-8000-000000000001';
  const payload = {
    data: {
      public_id: id,
      display_name: 'Test',
      restrictions: [],
      session: {
        public_id: id,
        activity_at: '2026-01-01 00:00:00+00',
        expires_at: '2026-01-01 00:30:00+00',
      },
      activity: {
        idle_minutes: 30,
        heartbeat_seconds: 30,
        warning_seconds: 60,
        extension: false,
      },
    },
  };
  for (const timezone of [
    'Europe/Warsaw',
    'America/New_York',
    'Pacific/Kiritimati',
  ]) {
    zone(timezone);
    const session = activityResponse(payload);
    expect(instantTimestamp(session.session.expires_at)).toBe(
      Date.parse('2026-01-01T00:30:00Z'),
    );
  }
  expect(() =>
    activityResponse({
      data: {
        ...payload.data,
        session: {
          ...payload.data.session,
          expires_at: '2026-02-30 00:30:00+00',
        },
      },
    }),
  ).toThrow();
});

it('application timezone formats known Auth history date changes without rewriting ordinary text', async () => {
  zone('America/New_York');
  const instant = '2026-01-01T00:30:00Z';
  mount(AuthHistoryDetail, {
    props: {
      open: true,
      loading: false,
      failed: false,
      locale: 'en',
      detail: {
        public_id: 'event',
        occurred_at: instant,
        action: 'lifecycle.changed',
        outcome: 'success',
        changes: [
          { field: 'expires_at', before: null, after: instant },
          { field: 'reason', before: null, after: instant },
        ],
      },
    },
  });
  await nextTick();
  const changes = document.querySelectorAll(
    '[data-history-detail] dl.border-t',
  );
  expect(changes[0]!.textContent).toContain('31/12/2025');
  expect(changes[0]!.textContent).toContain('19:30:00');
  expect(changes[1]!.textContent).toContain(instant);
});

it('application timezone formats backend moments consistently and never parses date-only or impossible text as an instant', () => {
  zone('America/New_York');
  const expected = formatApplicationInstant('2026-01-01T00:30:00Z', 'en');
  expect(expected).toContain('31/12/2025');
  expect(expected).toContain('19:30:00');
  for (const value of [
    '2026-01-01T00:30:00',
    '2026-01-01 00:30:00.000000',
    '2026-01-01 00:30:00.000000+00',
    '2026-01-01T02:30:00+02:00',
  ]) {
    expect(formatApplicationInstant(value, 'en')).toBe(expected);
    expect(instantTimestamp(value)).toBe(Date.parse('2026-01-01T00:30:00Z'));
  }
  for (const value of [
    '2026-01-01',
    '2026-02-30T12:00:00Z',
    '2026-01-01T25:00:00Z',
    'invalid',
  ]) {
    expect(formatApplicationInstant(value, 'en')).toBe('—');
  }
  expect(formatDateOnly('2026-01-01', 'pl')).toBe('01.01.2026');
  zone('Pacific/Kiritimati');
  expect(formatDateOnly('2026-01-01', 'pl')).toBe('01.01.2026');
  expect(formatApplicationInstant('2026-01-01T00:30:00Z', 'en')).toContain(
    '14:30:00',
  );
});

it('application timezone drives datetime defaults and reactive edits without timezone notes or lost invalid drafts', async () => {
  zone('America/New_York');
  const wrapper = mount(DateTimeInput, {
    props: {
      modelValue: '2026-01-01T00:30:00Z',
      label: 'Deadline',
      locale: 'en',
    },
    attachTo: document.body,
  });
  expect(wrapper.get('input').element.value).toBe('2025-12-31 19:30:00');
  expect(wrapper.text()).not.toMatch(/New_York|Time zone|UTC/);
  await wrapper.get('input').setValue('unfinished');
  zone('UTC');
  await nextTick();
  expect(wrapper.get('input').element.value).toBe('unfinished');
  wrapper.vm.reset();
  zone('Pacific/Kiritimati');
  await nextTick();
  expect(wrapper.get('input').element.value).toBe('2026-01-01 14:30:00');
  await wrapper.get('input').setValue('2026-01-01 15:00');
  expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([
    '2026-01-01T01:00:00.000Z',
  ]);
  await wrapper.setProps({ modelValue: '2026-01-01T01:00:00.000Z' });
  await wrapper.get('input').setValue('incomplete date');
  zone('Europe/Warsaw');
  await nextTick();
  expect(wrapper.get('input').element.value).toBe('incomplete date');
  expect(wrapper.get('input').attributes('aria-invalid')).toBe('true');
  await wrapper.get('button[aria-label="Choose: Deadline"]').trigger('click');
  expect(document.body.textContent).not.toMatch(/Europe\/Warsaw|Time zone/);
});

it('application timezone determines calendar today and footer year across midnight without shifting date-only models', async () => {
  vi.useFakeTimers({ toFake: ['Date'] });
  vi.setSystemTime(new Date('2026-01-01T00:30:00Z'));
  zone('America/Los_Angeles');
  const calendar = mount(AppCalendarPicker, {
    props: { modelValue: null, locale: 'en' },
  });
  const footer = mount(AppFooter);
  const date = mount(DateInput, {
    props: { modelValue: '2026-01-01', label: 'Calendar date', locale: 'en' },
  });
  expect(calendar.get('[aria-current="date"]').attributes('data-date')).toBe(
    '2025-12-31',
  );
  expect(footer.text()).toContain('© 2025');
  zone('Pacific/Kiritimati');
  await nextTick();
  expect(calendar.get('[aria-current="date"]').attributes('data-date')).toBe(
    '2026-01-01',
  );
  expect(footer.text()).toContain('© 2026');
  expect(date.get('input').element.value).toBe('2026-01-01');
});

it('application timezone formats the default dashboard observation while preserving the canonical machine timestamp', async () => {
  zone('America/New_York');
  const instant = '2026-01-01T00:30:00Z';
  const bootstrap: AdminBootstrap = {
    schema_version: 1,
    generation: 1,
    revision: 'test',
    activity: { remaining_seconds: 300, heartbeat_seconds: 30 },
    owners: [],
    presentation: { name: 'Test', logo: null, revision: 0 },
    dashboard: {
      widgets: [
        {
          id: 'clock',
          title: 'Clock',
          component: null,
          values: {},
          observed_at: instant,
          expires_at: null,
          diagnostic: null,
        },
      ],
    },
  };
  const wrapper = mount(AdminDashboard, { props: { bootstrap } });
  expect(wrapper.get('time').attributes('datetime')).toBe(instant);
  expect(wrapper.get('time').text()).toContain('19:30:00');
  expect(wrapper.get('time').text()).not.toContain(instant);
  zone('Asia/Kathmandu');
  await nextTick();
  expect(wrapper.get('time').text()).toContain('06:15:00');
});
