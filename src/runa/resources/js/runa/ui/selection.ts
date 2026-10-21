export interface SelectOption {
  value: string;
  label: string;
  disabled?: boolean;
}
/** Cursors are opaque; providers own transport, authorization and ordering. */
export interface SelectionPage {
  options: readonly SelectOption[];
  nextCursor?: string | null;
}
export type SelectionProvider = (request: {
  query: string;
  cursor: string | null;
  limit: number;
  signal: globalThis.AbortSignal;
}) => Promise<SelectionPage>;
