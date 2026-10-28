import type { IconName } from '@/runa/ui/icons';

interface WelcomeModule {
  id: string;
  name: { pl: string; en: string };
  description: { pl: string; en: string };
}

// Public source catalogue, independent of installation state and private grants.
// Names and descriptions have one source: each module's validated manifest.
const manifests = import.meta.glob<WelcomeModule>('../../*/module.json', {
  eager: true,
  import: 'default',
});
const icons: Record<string, IconName> = {
  'runa.audit': 'clipboard-list',
  'runa.auth': 'key',
  'runa.permissions': 'shield-lock',
};

export const welcomeModules = Object.values(manifests)
  .sort((a, b) => a.id.localeCompare(b.id))
  .map((module) => ({
    id: module.id,
    name: module.name,
    description: module.description,
    icon: icons[module.id] ?? 'layers-intersect',
  }));
