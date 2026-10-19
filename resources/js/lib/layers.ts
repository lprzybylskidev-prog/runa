import { shallowReactive, type InjectionKey, type Ref } from 'vue';
/** All transient layers share Escape/outside ownership, including modal children. */
const stack = shallowReactive<symbol[]>([]);
export function addLayer(key: symbol) {
  if (!stack.includes(key)) stack.push(key);
}
export function removeLayer(key: symbol) {
  const index = stack.indexOf(key);
  if (index !== -1) stack.splice(index, 1);
}
export function isTopLayer(key: symbol) {
  return stack.at(-1) === key;
}
export function layerIndex(key: symbol) {
  return stack.indexOf(key);
}
/** Modal-owned floating panels teleport inside the modal's inert/focus boundary. */
export const modalPortal: InjectionKey<
  Ref<globalThis.HTMLElement | undefined>
> = Symbol('modal-portal');
