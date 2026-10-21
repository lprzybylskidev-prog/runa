import { afterEach, expect, it } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { Button, Form, Select } from '@/runa/ui';
import type {
  ApplicationIdentity,
  ExportProvider,
  NavigationEntry,
  PreferenceStorage,
} from '@/runa/ui';

enableAutoUnmount(afterEach);

it('uses public controls without a layout, router, registry or global installation', async () => {
  const form = mount(Form, { slots: { default: 'Independent form' } });
  const button = mount(Button, { slots: { default: 'Independent action' } });
  const select = mount(Select, {
    props: {
      label: 'View',
      modelValue: 'all',
      options: [{ value: 'all', label: 'All' }],
    },
  });
  await button.trigger('click');
  expect(button.emitted('click')).toHaveLength(1);
  expect(form.attributes('novalidate')).toBeDefined();
  expect(select.get('button').text()).toBe('All');
  expect(document.querySelector('.minimal-page')).toBeNull();
});

// Compile the same type facade consumed by independent applications.
const identity: ApplicationIdentity = {
  applicationId: 'consumer',
  applicationName: 'Consumer',
  applicationVersion: '1.2.3',
  platformVersion: '0.1.2',
};
const navigation: NavigationEntry = {
  key: 'records',
  routeKey: 'records.index',
  moduleKey: 'records',
  label: 'Records',
  href: '/records',
  availability: 'available',
};
const storage: PreferenceStorage = {
  read: () => null,
  write: () => {},
  remove: () => {},
};
const provider: ExportProvider<{ search: string }> = {
  capabilities: { formats: ['csv'], scopes: ['selected'], cancellation: false },
  async start(request) {
    return {
      kind: 'download',
      download: {
        filename: request.filename,
        mimeType: 'text/csv',
        source: new Blob(['name\nexample'], { type: 'text/csv' }),
      },
    };
  },
};

it('lets a consumer supply selection and export providers through public contracts', async () => {
  expect(identity.applicationVersion).not.toBe(identity.platformVersion);
  expect(navigation.routeKey).toBe('records.index');
  expect(storage.read('unavailable')).toBeNull();
  const result = await provider.start(
    {
      format: 'csv',
      scope: {
        kind: 'selected',
        selection: {
          mode: 'all-matching',
          query: { search: 'example' },
          excludedPublicIds: ['01900000-0000-7000-8000-000000000001'],
          matchingCount: 5,
        },
      },
      columnKeys: ['name'],
      filename: 'records.csv',
    },
    new AbortController().signal,
  );
  expect(result.kind).toBe('download');
});
