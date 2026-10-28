/** One mounted account owns cancellation and mutation serialization. */
export interface AccountLifetime {
  readonly accountId: string;
  readonly signal: AbortSignal;
  readonly active: boolean;
}
export type AccountText = (pl: string, en: string) => string;
export type AccountPerform = (
  action: () => Promise<void>,
  clearSecrets?: boolean,
) => Promise<boolean | undefined>;
