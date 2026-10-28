export { default as ModulePage } from '../../components/ModulePage.vue';
export { default as ModuleSlot } from '../../components/ModuleSlot.vue';
export {
  createModuleComponents,
  decodeModuleFrontend,
  loadModuleFrontend,
  modulePages,
  moduleNavigation,
  useModuleFrontend,
} from '../../lib/moduleFrontend';
export type {
  ModuleFrontendView,
  ModuleComponentLoaders,
  ModuleComponents,
} from '../../lib/moduleFrontend';

export type * from './presentation';
export type {
  ModulePageDefinition,
  SlotContract,
} from '../../lib/moduleFrontend';
