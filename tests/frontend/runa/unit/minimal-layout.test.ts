import { afterEach, expect, it } from '@runa/testing/vitest';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import { h } from 'vue';
import {
  Footer,
  IdentityProvider,
  MinimalLayout,
  defaultIdentity,
  resolveIdentity,
} from '@/runa/ui';

enableAutoUnmount(afterEach);

it('renders independently with optional regions and a translator-compatible default', () => {
  const layout = mount(MinimalLayout, {
    slots: { default: ({ t }) => h('h1', t('title')), actions: 'Actions' },
  });
  expect(layout.get('main h1').text()).toBe('.runa');
  expect(layout.get('main').attributes('tabindex')).toBe('-1');
  expect(layout.get('.skip-link').attributes('href')).toBe('#main');
  expect(layout.find('.lg\\:grid-cols-2').exists()).toBe(true);
  expect(layout.find('.runa-art .art-core').exists()).toBe(true);
  expect(layout.get('main').text()).toContain('Actions');
  expect(layout.get('footer').text()).toContain(
    `Runa v${defaultIdentity.applicationVersion} | Oparte na Runa v${defaultIdentity.platformVersion}`,
  );
});

it('offers named header/content/illustration/actions/footer slots', () => {
  const layout = mount(MinimalLayout, {
    slots: {
      header: 'Custom header',
      content: 'Named content',
      default: 'Unused fallback',
      illustration: 'Illustration',
      actions: 'Actions',
      footer: '<footer>Custom footer</footer>',
    },
  });
  expect(layout.get('header').text()).toBe('Custom header');
  expect(layout.get('main').text()).toContain('Named content');
  expect(layout.text()).not.toContain('Unused fallback');
  expect(layout.find('.lg\\:grid-cols-2').exists()).toBe(true);
  expect(layout.get('footer').text()).toBe('Custom footer');
});

it('scopes reactive identity overrides and keeps independent application/platform versions', async () => {
  const host = mount(IdentityProvider, {
    props: {
      identity: {
        applicationId: 'atlas',
        applicationName: 'Atlas',
        applicationVersion: '2.4.1',
      },
    },
    slots: { default: () => h(MinimalLayout) },
  });
  expect(host.get('.brand').attributes('aria-label')).toBe('Atlas');
  expect(host.get('footer').text()).toContain(
    `Atlas v2.4.1 | Oparte na Runa v${defaultIdentity.platformVersion}`,
  );
  await host.setProps({
    identity: {
      applicationId: 'atlas',
      applicationName: 'Atlas Plus',
      applicationVersion: '2.4.1',
    },
  });
  expect(host.get('.brand').text()).toBe('Atlas Plus');
  expect(mount(Footer).text()).toContain(
    `Runa v${defaultIdentity.applicationVersion}`,
  );
});

it('validates branding URLs and supplies accessible fallback names', () => {
  for (const src of [
    'javascript:alert(1)',
    'data:image/svg+xml,test',
    '//untrusted.test/logo',
    '/\\untrusted.test/logo',
    'http://untrusted.test/logo',
    'https://user:pass@example.com/logo',
  ]) {
    expect(
      resolveIdentity({ logo: { src, accessibleName: '' } }).logo,
    ).toBeUndefined();
  }
  expect(
    resolveIdentity({
      applicationName: 'Atlas',
      logo: { src: '/logo.png', accessibleName: ' ' },
    }).logo?.accessibleName,
  ).toBe('Atlas');
  expect(
    resolveIdentity({
      logo: { src: 'https://example.com/logo.png', accessibleName: 'Logo' },
    }).logo?.src,
  ).toBe('https://example.com/logo.png');
});

it('falls back after a logo failure and retries a changed logo', async () => {
  const host = mount(IdentityProvider, {
    props: {
      identity: {
        applicationName: 'Atlas',
        logo: { src: '/missing-logo.png', accessibleName: 'Atlas logo' },
      },
    },
    slots: { default: () => h(MinimalLayout) },
  });
  await host.get('.brand img').trigger('error');
  expect(host.find('.brand img').exists()).toBe(false);
  expect(host.get('.brand').attributes('aria-label')).toBe('Atlas');
  await host.setProps({
    identity: {
      applicationName: 'Atlas',
      logo: { src: '/replacement.png', accessibleName: 'Atlas logo' },
    },
  });
  expect(host.get('.brand img').attributes('src')).toBe('/replacement.png');
});

it('configures each footer element, links and complete visibility', async () => {
  const footer = mount(Footer, {
    props: {
      options: { copyright: false, repository: false, application: false },
    },
  });
  expect(footer.text()).toBe(
    `Powered by Runa v${defaultIdentity.platformVersion}`,
  );
  await footer.setProps({
    options: {
      platform: false,
      copyrightText: '© Atlas',
      repositoryUrl: 'https://example.com/source',
      repositoryLabel: 'Source',
    },
  });
  expect(footer.text()).toContain('© Atlas');
  expect(footer.get('a').attributes('href')).toBe('https://example.com/source');
  expect(footer.text()).not.toContain('|');
  await footer.setProps({ options: { repositoryUrl: 'javascript:alert(1)' } });
  expect(footer.find('a').exists()).toBe(false);
  await footer.setProps({ options: { visible: false } });
  expect(footer.find('footer').exists()).toBe(false);
  const layout = mount(MinimalLayout, {
    props: { footer: { visible: false } },
    slots: { footer: '<footer>Override</footer>' },
  });
  expect(layout.find('footer').exists()).toBe(false);
});

it('allows explicitly hiding the built-in illustration', () => {
  const layout = mount(MinimalLayout, {
    props: { illustration: false },
    slots: { default: 'Content' },
  });
  expect(layout.find('.runa-art').exists()).toBe(false);
  expect(layout.find('.lg\\:grid-cols-2').exists()).toBe(false);
  expect(layout.get('main').text()).toBe('Content');
});
