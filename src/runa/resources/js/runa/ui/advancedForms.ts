import type { SelectOption } from './selection';
/** # = ASCII digit, A = Unicode letter, * = letter/digit; backslash escapes literals. */
export interface MaskResult {
  raw: string | null;
  formatted: string;
  complete: boolean;
  /** UTF-16 boundaries after each raw character, for native selection ranges. */
  boundaries: number[];
}
export interface DependentField {
  key: string;
  dependsOn?: readonly string[];
  options?: readonly SelectOption[];
  load?: (request: {
    parents: Readonly<Record<string, string>>;
    signal: globalThis.AbortSignal;
  }) => Promise<readonly SelectOption[]>;
}
export interface WizardStep {
  key: string;
  label: string;
  description?: string;
}
