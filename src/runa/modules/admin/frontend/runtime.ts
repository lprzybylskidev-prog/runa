import { onScopeDispose, ref, shallowRef } from 'vue';
import type { AdminController, AdminPageController } from './contracts';

/** One current mutation intent per page, shared by independent sections and aggregate forms. */
export function createPageRuntime() {
  return {
    busy: ref(false),
    revision: shallowRef<string>(),
    changed: ref(0),
    beforeLeave: new Set<() => Promise<boolean>>(),
  };
}
export const pageRuntimes = new WeakMap<
  AdminPageController,
  ReturnType<typeof createPageRuntime>
>();
export function pageRuntime(page: AdminPageController) {
  let runtime = pageRuntimes.get(page);
  if (!runtime) {
    runtime = createPageRuntime();
    pageRuntimes.set(page, runtime);
    onScopeDispose(() => pageRuntimes.delete(page));
  }
  return runtime;
}
export type NavigationGuard = (
  action: () => void | Promise<void>,
) => Promise<boolean>;
export const navigationGuards = new WeakMap<
  AdminController,
  Set<NavigationGuard>
>();
export async function navigateWithGuards(
  admin: AdminController,
  action: () => void,
): Promise<boolean> {
  const guards = [...(navigationGuards.get(admin) ?? [])];
  async function run(index: number): Promise<boolean> {
    const guard = guards[index];
    if (!guard) {
      action();
      return true;
    }
    let accepted = false;
    return (
      (await guard(async () => {
        accepted = await run(index + 1);
      })) && accepted
    );
  }
  return run(0);
}
