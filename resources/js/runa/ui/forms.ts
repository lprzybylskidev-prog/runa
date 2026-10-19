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

export interface DateRangeValue {
  start: string | null;
  end: string | null;
}
export type TemporalKind = 'date' | 'time' | 'datetime-local';
export type TimeDisambiguation = 'reject' | 'earlier' | 'later';
export type ZonedDateTimeResult =
  | { valid: true; value: string | null }
  | {
      valid: false;
      reason: 'format' | 'timezone' | 'gap' | 'ambiguous';
      candidates: readonly string[];
    };
export interface TemporalInputProps {
  modelValue: string | null;
  label: string;
  locale?: 'pl' | 'en';
  helper?: string;
  error?: string | readonly string[];
  invalidMessage?: string;
  disabled?: boolean;
  readonly?: boolean;
  required?: boolean;
}
export interface BooleanInputProps {
  hideLabel?: boolean;
  modelValue: boolean | null;
  label: string;
  helper?: string;
  error?: string | readonly string[];
  disabled?: boolean;
  readonly?: boolean;
  required?: boolean;
}
export interface RadioOption {
  value: string;
  label: string;
  disabled?: boolean;
}
export interface FileConstraints {
  multiple?: boolean;
  maxFiles?: number;
  maxBytes?: number;
  accept?: string;
}
export interface FileIssue {
  code: 'count' | 'size' | 'type';
  fileName?: string;
}
