import type { VNode } from 'vue';
import type { FooterOptions } from './identity';
import type { Locale, Theme } from './preferences';
import type { NavigationEntry, RouteContext } from './navigation';
import type { PreferenceStore } from '../../lib/preferenceStore';
import type { MessageKey } from '../../locales/messages';

export interface MinimalLayoutProps {
  footer?: FooterOptions | undefined;
  homeHref?: string;
  illustration?: boolean;
  constellationGlow?: boolean;
  orbitMotion?: boolean;
  staticPreferences?: { locale: Locale; theme: Theme } | undefined;
}
export interface ApplicationLayoutProps {
  navigation: readonly NavigationEntry[];
  topNavigation?: readonly NavigationEntry[];
  current?: RouteContext | undefined;
  title: string;
  description?: string;
  sidebar?: boolean;
  topbar?: boolean;
  width?: 'contained' | 'fluid';
  footer?: FooterOptions | undefined;
  homeHref?: string;
  preferenceStore?: PreferenceStore | undefined;
  userName?: string;
  userInitials?: string;
  userAvatar?: string;
  userLoading?: boolean;
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
export interface ApplicationLayoutSlots {
  default?(scope: LayoutSlotContext): VNode[];
  content?(scope: LayoutSlotContext): VNode[];
  'sidebar-bottom'?(scope: LayoutSlotContext & { collapsed: boolean }): VNode[];
  'user-menu'?(scope: LayoutSlotContext): VNode[];
  heading?(scope: LayoutSlotContext): VNode[];
  actions?(scope: LayoutSlotContext): VNode[];
  breadcrumbs?(scope: LayoutSlotContext): VNode[];
  statistics?(scope: LayoutSlotContext): VNode[];
  toolbar?(scope: LayoutSlotContext): VNode[];
  supplementary?(scope: LayoutSlotContext): VNode[];
  footer?(scope: LayoutSlotContext): VNode[];
}
