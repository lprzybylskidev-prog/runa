import { moduleCatalogue } from '@/runa/ui';

// Public source metadata; installation and grants are separate runtime contracts.
export const welcomeModules = moduleCatalogue.filter((module) =>
  module.id.startsWith('runa.'),
);
