import type { VNode } from 'vue';
import type { FooterOptions } from './identity';
import type { Locale, Theme } from './preferences';
import type { NavigationEntry, RouteContext } from './navigation';
import type { PreferenceStore } from '../../lib/preferenceStore';
import type { MessageKey } from '../../locales/messages';

export interface MinimalLayoutProps {
  readiness?:
    import('../../composables/useViewReadiness').ViewReadiness | undefined;
  footer?: FooterOptions | undefined;
  homeHref?: string;
  illustration?: boolean;
  constellationGlow?: boolean;
  orbitMotion?: boolean;
  staticPreferences?: { locale: Locale; theme: Theme } | undefined;
}
export interface ApplicationLayoutProps {
  readiness?:
    import('../../composables/useViewReadiness').ViewReadiness | undefined;
  /** Fixed presentation locale; does not update account language. */
  locale?: Locale | undefined;
  navigation: readonly NavigationEntry[];
  topNavigation?: readonly NavigationEntry[] | undefined;
  navigationLoading?: boolean | undefined;
  current?: RouteContext | undefined;
  title: string;
  description?: string | undefined;
  sidebar?: boolean | undefined;
  topbar?: boolean | undefined;
  width?: 'contained' | 'fluid' | undefined;
  footer?: FooterOptions | undefined;
  homeHref?: string | undefined;
  preferenceStore?: PreferenceStore | undefined;
  userName?: string | undefined;
  userInitials?: string | undefined;
  userAvatar?: string | undefined;
  userAvatarImage?:
    import('../../composables/useImageReadiness').ImageReadiness | undefined;
  userLoading?: boolean | undefined;
}
export interface LayoutSlotContext {
  t: (key: MessageKey) => string;
}
export interface MinimalLayoutSlots {
  default?(scope: LayoutSlotContext): VNode[];
  content?(scope: LayoutSlotContext): VNode[];
  header?(scope: LayoutSlotContext): VNode[];
  'header-actions'?(scope: LayoutSlotContext): VNode[];
  illustration?(scope: LayoutSlotContext): VNode[];
  actions?(scope: LayoutSlotContext): VNode[];
  footer?(scope: LayoutSlotContext): VNode[];
}
export interface ApplicationBreadcrumbSlotContext extends LayoutSlotContext {
  navigation?: readonly NavigationEntry[];
  topNavigation?: readonly NavigationEntry[];
  current?: RouteContext | undefined;
  navigate?: (href: string, event: MouseEvent) => void;
}
export interface ApplicationLayoutSlots {
  default?(scope: LayoutSlotContext): VNode[];
  content?(scope: LayoutSlotContext): VNode[];
  'sidebar-bottom'?(scope: LayoutSlotContext & { collapsed: boolean }): VNode[];
  'user-menu'?(scope: LayoutSlotContext): VNode[];
  heading?(scope: LayoutSlotContext): VNode[];
  actions?(scope: LayoutSlotContext): VNode[];
  breadcrumbs?(scope: ApplicationBreadcrumbSlotContext): VNode[];
  statistics?(scope: LayoutSlotContext): VNode[];
  toolbar?(scope: LayoutSlotContext): VNode[];
  supplementary?(scope: LayoutSlotContext): VNode[];
  footer?(scope: LayoutSlotContext): VNode[];
}
