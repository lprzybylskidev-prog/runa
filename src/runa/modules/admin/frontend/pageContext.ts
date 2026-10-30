import type { AdminController, AdminPageController } from './contracts';

/** Internal ownership link; custom presentation uses public controllers, never session copies. */
export const pageOwners = new WeakMap<AdminPageController, AdminController>();
