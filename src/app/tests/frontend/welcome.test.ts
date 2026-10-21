import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import WelcomePage from '@/App.vue';

describe('application welcome', () => {
  it('renders the welcome page', () => {
    const page = mount(WelcomePage);
    expect(page.get('main h1').text()).toBe('Runa');
    page.unmount();
  });
});
