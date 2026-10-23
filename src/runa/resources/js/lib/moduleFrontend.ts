import {
  defineAsyncComponent,
  markRaw,
  onScopeDispose,
  shallowRef,
  type Component,
} from 'vue';
import { request } from './http';
import type { NavigationEntry, PageDescriptor } from '../runa/ui/navigation';
import type { IconName } from '../runa/ui/icons';

export interface ModulePageDefinition {
  readonly routeKey: string;
  readonly moduleKey: string;
  readonly areaKey: string | null;
  readonly href: string;
  readonly serverRouteName: string;
  readonly names: Readonly<Record<'pl' | 'en', string>>;
  readonly icon: IconName;
  readonly component: string;
  readonly manifestHash: string;
  readonly availability: 'available' | 'disabled' | 'hidden';
  readonly reason: string | null;
}
export interface SlotContract {
  readonly id: string;
  readonly contract: string;
  readonly version: number;
  readonly props: Readonly<Record<string, 'string' | 'number' | 'boolean'>>;
  readonly schemaVersion: 1;
}
export interface ModuleFrontendView {
  readonly schemaVersion: 1;
  readonly generation: number;
  readonly pages: readonly ModulePageDefinition[];
  readonly slots: Readonly<
    Record<
      string,
      {
        readonly contract: SlotContract;
        readonly contributions: readonly {
          readonly id: string;
          readonly component: string;
          readonly manifestHash: string;
          readonly target: SlotContract;
        }[];
      }
    >
  >;
  readonly diagnostics: Readonly<Record<string, string>>;
}
export type ModuleComponentLoaders = Readonly<
  Record<
    string,
    {
      readonly manifestHash: string;
      readonly load: () => Promise<{ default: Component }>;
    }
  >
>;
export type ModuleComponents = Readonly<
  Record<
    string,
    { readonly manifestHash: string; readonly component: Component }
  >
>;
export function createModuleComponents(
  loaders: ModuleComponentLoaders,
): ModuleComponents {
  return Object.fromEntries(
    Object.entries(loaders).map(([key, loader]) => {
      if (!/^(app|runa)\.[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/.test(key))
        throw new TypeError('Invalid module component key.');
      return [
        key,
        {
          manifestHash: hash(loader.manifestHash),
          component: markRaw(
            defineAsyncComponent(() =>
              loader.load().then((module) => module.default),
            ),
          ),
        },
      ];
    }),
  );
}
function hash(value: unknown): string {
  if (typeof value !== 'string' || !/^[a-f0-9]{64}$/.test(value))
    throw new TypeError('Invalid module build hash.');
  return value;
}
function object(value: unknown): Record<string, unknown> {
  if (value === null || typeof value !== 'object' || Array.isArray(value))
    throw new TypeError('Invalid frontend metadata.');
  return value as Record<string, unknown>;
}
function text(value: unknown): string {
  if (typeof value !== 'string' || !value.length || value.length > 256)
    throw new TypeError('Invalid frontend text.');
  return value;
}
function contract(value: unknown): SlotContract {
  const data = object(value);
  const props = object(data.props);
  if (
    data.schemaVersion !== 1 ||
    !Number.isSafeInteger(data.version) ||
    Number(data.version) < 1 ||
    Object.keys(props).length > 32
  )
    throw new TypeError('Invalid slot contract.');
  for (const [key, type] of Object.entries(props)) {
    if (
      !/^[a-z][a-zA-Z0-9]{0,63}$/.test(key) ||
      key.startsWith('on') ||
      ['key', 'ref', 'class', 'style'].includes(key) ||
      !['string', 'number', 'boolean'].includes(String(type))
    )
      throw new TypeError('Invalid slot props.');
  }
  return {
    schemaVersion: 1,
    id: text(data.id),
    contract: text(data.contract),
    version: Number(data.version),
    props: props as SlotContract['props'],
  };
}
const icons = [
  'layers-intersect',
  'sun',
  'moon',
  'device-desktop',
  'file-type-csv',
  'file-type-xls',
  'file-type-pdf',
  'printer',
  'adjustments-horizontal',
];
export function decodeModuleFrontend(value: unknown): ModuleFrontendView {
  const data = object(value);
  if (
    data.schemaVersion !== 1 ||
    !Number.isSafeInteger(data.generation) ||
    Number(data.generation) < 0 ||
    !Array.isArray(data.pages) ||
    data.pages.length > 16384
  )
    throw new TypeError('Invalid frontend schema.');
  const paths = new Set<string>();
  const keys = new Set<string>();
  const pages = data.pages.map((value: unknown): ModulePageDefinition => {
    const page = object(value),
      names = object(page.names);
    const moduleKey = text(page.moduleKey),
      routeKey = text(page.routeKey),
      component = text(page.component),
      href = text(page.href);
    if (
      !/^(app|runa)\.[a-z][a-z0-9_]*$/.test(moduleKey) ||
      !routeKey.startsWith(moduleKey + '.') ||
      !component.startsWith(moduleKey + '.') ||
      !/^\/modules\/(app|runa)\/[a-z][a-z0-9_]*\/[a-z][a-z0-9_]*$/.test(href) ||
      paths.has(href) ||
      keys.has(routeKey) ||
      !['available', 'disabled', 'hidden'].includes(
        String(page.availability),
      ) ||
      !icons.includes(String(page.icon))
    )
      throw new TypeError('Invalid frontend page.');
    paths.add(href);
    keys.add(routeKey);
    return {
      routeKey,
      moduleKey,
      component,
      href,
      manifestHash: hash(page.manifestHash),
      serverRouteName: text(page.serverRouteName),
      names: { pl: text(names.pl), en: text(names.en) },
      areaKey: page.areaKey === null ? null : text(page.areaKey),
      availability: page.availability as ModulePageDefinition['availability'],
      icon: page.icon as IconName,
      reason: page.reason === null ? null : text(page.reason),
    };
  });
  const slots = Object.fromEntries(
    Object.entries(object(data.slots)).map(([id, value]) => {
      const slot = object(value),
        host = contract(slot.contract);
      if (
        host.id !== id ||
        !Array.isArray(slot.contributions) ||
        slot.contributions.length > 16384
      )
        throw new TypeError('Invalid slot.');
      const seen = new Set<string>();
      const contributions = slot.contributions.map((value: unknown) => {
        const entry = object(value),
          target = contract(entry.target),
          key = text(entry.id);
        if (
          seen.has(key) ||
          target.id !== id ||
          target.contract !== host.contract ||
          target.version !== host.version ||
          Object.keys(target.props).length !== Object.keys(host.props).length ||
          Object.entries(host.props).some(
            ([key, type]) => target.props[key] !== type,
          )
        )
          throw new TypeError('Incompatible slot.');
        seen.add(key);
        return {
          id: key,
          component: text(entry.component),
          manifestHash: hash(entry.manifestHash),
          target,
        };
      });
      return [id, { contract: host, contributions }];
    }),
  );
  const diagnostics = Object.fromEntries(
    Object.entries(object(data.diagnostics)).map(([key, value]) => [
      key,
      text(value),
    ]),
  );
  return {
    schemaVersion: 1,
    generation: Number(data.generation),
    pages,
    slots,
    diagnostics,
  };
}
export async function loadModuleFrontend(
  signal: AbortSignal,
): Promise<ModuleFrontendView> {
  return decodeModuleFrontend(await request('/modules/frontend', { signal }));
}
/** Explicit refresh clears stale presentation immediately, cancels and rejects late responses. */
export function useModuleFrontend(load = loadModuleFrontend) {
  const view = shallowRef<ModuleFrontendView | null>(null);
  const error = shallowRef(false);
  let sequence = 0;
  let controller: AbortController | undefined;
  async function refresh() {
    const current = ++sequence;
    controller?.abort();
    controller = new AbortController();
    view.value = null;
    error.value = false;
    try {
      const result = await load(controller.signal);
      if (sequence === current) view.value = result;
    } catch {
      if (sequence === current) error.value = true;
    }
  }
  onScopeDispose(() => {
    sequence++;
    controller?.abort();
  });
  return { view, error, refresh };
}
export function modulePages(
  view: ModuleFrontendView,
  locale: 'pl' | 'en',
): PageDescriptor[] {
  return view.pages.map((page) => ({
    routeKey: page.routeKey,
    moduleKey: page.moduleKey,
    href: page.href,
    serverRouteName: page.serverRouteName,
    title: page.names[locale],
    ...(page.areaKey === null ? {} : { areaKey: page.areaKey }),
  }));
}
export function moduleNavigation(
  view: ModuleFrontendView,
  locale: 'pl' | 'en',
): NavigationEntry[] {
  return modulePages(view, locale).map((page, index) => ({
    ...page,
    key: page.routeKey,
    label: page.title,
    icon: view.pages[index]!.icon,
    availability: view.pages[index]!.availability,
  }));
}
