/** Public, shell-independent imports. Keep implementation paths private to the facade. */
export { default as Button } from '../../components/AppButton.vue';
export { default as Tooltip } from '../../components/AppTooltip.vue';
export { default as Icon } from '../../components/AppIcon.vue';
export { default as Form } from '../../components/AppForm.vue';
export { default as Select } from '../../components/AppSelect.vue';
export { default as LoadingOverlay } from '../../components/AppLoadingOverlay.vue';
export { default as Logo } from '../../components/RunaLogo.vue';
export { default as MinimalLayout } from '../../layouts/MinimalLayout.vue';
export { default as IdentityProvider } from '../../components/AppIdentityProvider.vue';
export { default as Footer } from '../../components/AppFooter.vue';
export { defaultIdentity, resolveIdentity, useIdentity } from './identity';
export type * from './identity';
export type * from './navigation';
export type * from './preferences';
export type * from './exports';

export { default as ApplicationLayout } from '../../layouts/ApplicationLayout.vue';
export { createPageResolver, usePageResolver } from '../../lib/pageResolver';
export {
  createPreferenceStore,
  defaultPreferenceStore,
  preferenceKey,
} from '../../lib/preferenceStore';
export type {
  PreferenceStore,
  PreferenceDefinition,
} from '../../lib/preferenceStore';
export { usePreference } from '../../composables/usePreference';
export type { IconName } from './icons';
