import { describe, expect, it, vi } from '@runa/testing/vitest';
import { mount } from '@runa/testing/vue';
import WelcomePage from '@/App.vue';

describe('application welcome', () => {
  it('renders the welcome page', async () => {
    const page = mount(WelcomePage);
    await vi.waitFor(() => expect(page.get('main h1').text()).toBe('.runa'));
    page.unmount();
  });
});
