import { afterEach, expect, it, vi } from '@runa/testing/vitest';
import { h } from 'vue';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import { HttpErrorContent, IdentityProvider } from '@/runa/ui';
import CustomerErrorLayout from './fixtures/customization/CustomerErrorLayout.vue';

enableAutoUnmount(afterEach);
it('customization: static error composition keeps locale, retry, identity and request placeholders without requests', () => {
  const fetch = vi.spyOn(window, 'fetch');
  const error = {
    status: 503,
    locale: 'en' as const,
    theme: 'dark' as const,
    code: 'service_unavailable',
    title: 'Service unavailable',
    description: 'Please retry later.',
    home: 'Home',
    retry: 'Retry',
  };
  const w = mount(IdentityProvider, {
    props: {
      identity: {
        applicationId: 'customer',
        applicationName: 'Customer app',
        applicationVersion: '1.2.3',
      },
    },
    slots: {
      default: () =>
        h(
          CustomerErrorLayout,
          {
            error,
            staticPreferences: { locale: 'en', theme: 'dark' },
            footer: { copyright: false },
          },
          { default: () => h(HttpErrorContent, error) },
        ),
    },
  });
  expect(w.text()).toContain('Customer support · 503');
  expect(w.text()).toContain('Customer app');
  expect(w.text()).toContain('Service unavailable');
  expect(
    w.get('[data-error-request-id]').attributes('data-error-request-id'),
  ).toBe('__RUNA_REQUEST_ID__');
  expect(w.find('form input[name="theme"]').attributes('value')).toBe('dark');
  expect(w.html()).not.toMatch(/<script\b|\sstyle=|\son[a-z]+=/i);
  expect(fetch).not.toHaveBeenCalled();
});
