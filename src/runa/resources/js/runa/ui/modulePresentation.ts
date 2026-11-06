import type { IconName } from './icons';
export interface ModulePresentation {
  readonly id: string;
  readonly name: { readonly pl: string; readonly en: string };
  readonly description: { readonly pl: string; readonly en: string };
  readonly icon: IconName;
}
type ModuleManifestPresentation = Omit<ModulePresentation, 'icon'>;
const manifests = {
  ...import.meta.glob<ModuleManifestPresentation>(
    '../../../../modules/*/module.json',
    { eager: true, import: 'default' },
  ),
  ...import.meta.glob<ModuleManifestPresentation>(
    '../../../../../app/modules/*/module.json',
    { eager: true, import: 'default' },
  ),
};
const icons: Readonly<Record<string, IconName>> = {
  'runa.admin': 'adjustments-horizontal',
  'runa.audit': 'clipboard-list',
  'runa.auth': 'key',
  'runa.permissions': 'shield-lock',
};
/** Source metadata only; callers retain runtime availability and authorization. */
export const moduleCatalogue: readonly ModulePresentation[] = Object.values(
  manifests,
)
  .sort((a, b) => a.id.localeCompare(b.id))
  .map((module) => ({
    id: module.id,
    name: module.name,
    description: module.description,
    icon: icons[module.id] ?? 'layers-intersect',
  }));
export function getModulePresentation(id: string): ModulePresentation {
  return (
    moduleCatalogue.find((module) => module.id === id) ?? {
      id,
      name: { pl: id, en: id },
      description: { pl: '', en: '' },
      icon: 'layers-intersect',
    }
  );
}
