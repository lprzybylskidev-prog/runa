/** Data only: URL validation and provider defaults arrive with the minimal layout. */
export interface ApplicationIdentity {
  readonly applicationId: string;
  readonly applicationName: string;
  readonly applicationVersion: string;
  readonly platformVersion: string;
  readonly logo?: { readonly src: string; readonly accessibleName: string };
}

export interface FooterOptions {
  readonly visible?: boolean;
  readonly application?: boolean;
  readonly platform?: boolean;
  readonly copyright?: boolean;
  readonly repository?: boolean;
}
