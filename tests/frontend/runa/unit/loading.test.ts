import { afterEach, expect, it } from '@runa/testing/vitest';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import { effectScope, nextTick } from 'vue';
import {
  Spinner,
  Skeleton,
  EmptyState,
  Progress,
  AsyncContent,
  Statistic,
  Statistics,
  createPreferenceStore,
  preferenceKey,
  useStatisticsMode,
} from '../../../../src/runa/resources/js/runa/ui';
enableAutoUnmount(afterEach);
it('names standalone spinners while embedded spinners remain decorative', () => {
  const w = mount(Spinner, { props: { label: 'Loading' } });
  expect(w.attributes('role')).toBe('status');
  expect(w.attributes('aria-label')).toBe('Loading');
  const decorative = mount(Spinner, { props: { label: '', decorative: true } });
  expect(decorative.attributes('role')).toBeUndefined();
  expect(decorative.attributes('aria-hidden')).toBe('true');
});
it('hides bounded skeleton geometry from accessibility and renders empty actions', () => {
  const w = mount(Skeleton, { props: { lines: 500 } });
  expect(w.attributes('aria-hidden')).toBe('true');
  expect(w.findAll('span')).toHaveLength(10);
  const empty = mount(EmptyState, {
    props: { title: 'No records', description: 'Change filters' },
    slots: { actions: '<button>Clear filters</button>' },
  });
  expect(empty.get('h3').text()).toBe('No records');
  expect(empty.get('button').text()).toBe('Clear filters');
});
it('exposes real progress units, clamps invalid values and transitions to indeterminate', async () => {
  const w = mount(Progress, {
    props: { label: 'Upload', value: 10, max: 20, valueText: '10 of 20 files' },
  });
  const bar = () => w.get('[role="progressbar"]');
  expect(bar().attributes('aria-valuenow')).toBe('10');
  expect(bar().attributes('aria-valuemax')).toBe('20');
  expect(bar().attributes('aria-valuetext')).toBe('10 of 20 files');
  expect(w.text()).toContain('50%');
  await w.setProps({ value: 50 });
  expect(bar().attributes('aria-valuenow')).toBe('20');
  await w.setProps({ value: -2 });
  expect(bar().attributes('aria-valuenow')).toBe('0');
  await w.setProps({ value: null });
  expect(bar().attributes('aria-valuenow')).toBeUndefined();
  expect(w.text()).not.toContain('%');
  await w.setProps({ value: Number.NaN, max: Number.POSITIVE_INFINITY });
  expect(bar().attributes('aria-valuenow')).toBeUndefined();
  expect(bar().attributes('aria-valuemax')).toBe('100');
});
it('renders only the requested async state and emits retry without claiming a result', async () => {
  const w = mount(AsyncContent, {
    props: {
      state: 'loading',
      loadingLabel: 'Loading list',
      errorTitle: 'Load failed',
      errorMessage: 'Keep your filters',
      retryLabel: 'Retry',
      emptyTitle: 'No results',
    },
    slots: { default: '<button>Loaded content</button>' },
  });
  expect(w.attributes('aria-busy')).toBe('true');
  expect(w.find('button').exists()).toBe(false);
  expect(w.get('[role="status"]').attributes('aria-label')).toBe(
    'Loading list',
  );
  await w.setProps({ state: 'error' });
  expect(w.attributes('aria-busy')).toBe('false');
  expect(w.get('[role="alert"]').text()).toContain('Keep your filters');
  await w.get('button').trigger('click');
  expect(w.emitted('retry')).toHaveLength(1);
  expect(w.find('[role="alert"]').exists()).toBe(true);
  await w.setProps({ state: 'empty' });
  expect(w.text()).toBe('No results');
  await w.setProps({ state: 'success' });
  expect(w.get('button').text()).toBe('Loaded content');
  expect(w.find('[role="alert"]').exists()).toBe(false);
});
it('supports custom async states and accessible KPI visualization composition', async () => {
  const w = mount(AsyncContent, {
    props: { state: 'loading' },
    slots: {
      loading: 'Custom skeleton',
      error: 'Custom error',
      empty: 'Custom empty',
      success: 'Custom result',
    },
  });
  expect(w.text()).toBe('Custom skeleton');
  for (const state of ['error', 'empty', 'success'] as const) {
    await w.setProps({ state });
    expect(w.text()).toBe(
      {
        error: 'Custom error',
        empty: 'Custom empty',
        success: 'Custom result',
      }[state],
    );
  }
  const kpi = mount(Statistic, {
    props: {
      label: 'Revenue',
      value: '123.45',
      trend: 'Up 5%',
      tone: 'success',
    },
    slots: { visualization: '<p>Accessible chart summary</p>' },
  });
  expect(kpi.get('dt').text()).toBe('Revenue');
  expect(kpi.get('dd').text()).toBe('123.45');
  expect(kpi.text()).toContain('Up 5%');
  expect(kpi.text()).toContain('Accessible chart summary');
});
it('keeps statistics mode controlled, passes it to content and supports disabled state', async () => {
  const w = mount(Statistics, {
    props: {
      modelValue: 'global',
      label: 'Statistics',
      globalLabel: 'Global',
      filteredLabel: 'Filtered',
    },
    slots: { default: ({ mode }: { mode: string }) => mode },
  });
  await w.findAll('button')[1]!.trigger('click');
  expect(w.emitted('update:modelValue')).toEqual([['filtered']]);
  expect(w.findAll('button')[0]!.attributes('aria-pressed')).toBe('true');
  await w.setProps({ modelValue: 'filtered' });
  expect(w.findAll('button')[1]!.attributes('aria-pressed')).toBe('true');
  await w.setProps({ disabled: true });
  expect(
    w.findAll('button').every((b) => b.attributes('disabled') !== undefined),
  ).toBe(true);
});
it('persists a validated mode independently per view and honors per-view defaults', async () => {
  const values = new Map<string, string>();
  const store = createPreferenceStore({
    read: (key) => values.get(key) ?? null,
    write: (key, value) => {
      values.set(key, value);
    },
    remove: (key) => {
      values.delete(key);
    },
  });
  const scopeA = { applicationId: 'test', moduleKey: 'reports', viewKey: 'a' };
  const scopeB = { ...scopeA, viewKey: 'b' };
  const effects = effectScope();
  try {
    const a = effects.run(() => useStatisticsMode(scopeA, 'global', store))!;
    const b = effects.run(() => useStatisticsMode(scopeB, 'filtered', store))!;
    expect(a.value).toBe('global');
    expect(b.value).toBe('filtered');
    a.value = 'filtered';
    b.value = 'global';
    await nextTick();
    expect(
      effects.run(() => useStatisticsMode(scopeA, 'global', store))!.value,
    ).toBe('filtered');
    expect(
      effects.run(() => useStatisticsMode(scopeB, 'filtered', store))!.value,
    ).toBe('global');
    values.set(
      preferenceKey(scopeA, 'statistics.mode'),
      JSON.stringify({ version: 1, value: 'invalid' }),
    );
    expect(
      effects.run(() => useStatisticsMode(scopeA, 'global', store))!.value,
    ).toBe('global');
  } finally {
    effects.stop();
  }
});
it('preserves a session mode when browser storage is denied', async () => {
  const store = createPreferenceStore({
    read: () => {
      throw new Error('denied');
    },
    write: () => {
      throw new Error('denied');
    },
    remove: () => {},
  });
  const scope = {
    applicationId: 'test',
    moduleKey: 'reports',
    viewKey: 'denied',
  };
  const effects = effectScope();
  try {
    const value = effects.run(() => useStatisticsMode(scope, 'global', store))!;
    value.value = 'filtered';
    await nextTick();
    expect(
      effects.run(() => useStatisticsMode(scope, 'global', store))!.value,
    ).toBe('filtered');
  } finally {
    effects.stop();
  }
});
