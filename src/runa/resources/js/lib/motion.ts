/** Exiting DOM must stop accepting input/announcements before its visual exit ends. */
export function deactivateMotionElement(element: Element) {
  if (element instanceof globalThis.HTMLElement) {
    element.inert = true;
    element.setAttribute('aria-hidden', 'true');
  }
}
export function activateMotionElement(element: Element) {
  if (element instanceof globalThis.HTMLElement) {
    element.inert = false;
    element.removeAttribute('aria-hidden');
  }
}
