import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import WelcomePage from '@/App.vue';

describe('application welcome', () => {
  it('renders the welcome page', async () => {
    const page = mount(WelcomePage);
    await vi.waitFor(() => expect(page.get('main h1').text()).toBe('Runa'));
    page.unmount();
  });
});
