/** Shared ownership prevents a drawer/dialog from unlocking another active layer. */
const owners = new Set<symbol>();
let saved: { body: string; html: string; padding: string } | undefined;
export function lockDocumentScroll(): () => void {
  const key = Symbol('scroll-lock');
  if (!owners.size) {
    saved = {
      body: document.body.style.overflow,
      html: document.documentElement.style.overflow,
      padding: document.body.style.paddingRight,
    };
    const gap = window.innerWidth - document.documentElement.clientWidth;
    if (gap > 0)
      document.body.style.paddingRight = `${parseFloat(getComputedStyle(document.body).paddingRight) + gap}px`;
    document.body.style.overflow = 'hidden';
    document.documentElement.style.overflow = 'hidden';
  }
  owners.add(key);
  return () => {
    if (!owners.delete(key) || owners.size || !saved) return;
    document.body.style.overflow = saved.body;
    document.documentElement.style.overflow = saved.html;
    document.body.style.paddingRight = saved.padding;
    saved = undefined;
  };
}
