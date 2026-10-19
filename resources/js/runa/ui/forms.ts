export type TextInputType =
  'text' | 'textarea' | 'password' | 'email' | 'url' | 'tel';
export type NumericKind = 'integer' | 'decimal' | 'currency' | 'percentage';
export type FieldErrors = Record<string, readonly string[]>;
export type FormValue =
  string | number | boolean | null | FormValue[] | { [key: string]: FormValue };
export type FormFailure = 'validation' | 'conflict' | 'unexpected' | null;
export interface DecimalOptions {
  kind?: NumericKind;
  locale?: 'pl' | 'en';
  scale?: number;
}
export type DecimalResult =
  | { valid: true; value: string | null }
  | { valid: false; reason: 'format' | 'scale' };
