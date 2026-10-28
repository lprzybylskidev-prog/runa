import type { VNode } from 'vue';
import type {
  ModuleFrontendView,
  ModuleComponents,
} from '../../lib/moduleFrontend';

export interface ModulePageProps {
  view: ModuleFrontendView | null;
  components: ModuleComponents;
  href: string;
  locale?: 'pl' | 'en';
  unavailableText?: string;
}
export interface ModulePageSlots {
  unavailable?(scope: { reason: string }): VNode[];
}
export interface ModuleSlotProps {
  view: ModuleFrontendView | null;
  components: ModuleComponents;
  name: string;
  values?: Readonly<Record<string, string | number | boolean>>;
}
export interface ModuleSlotSlots {
  default?(): VNode[];
}
