/** Availability controls presentation; the server still authorizes every request. */
export interface NavigationEntry {
  readonly key: string;
  readonly routeKey: string;
  readonly moduleKey: string;
  readonly areaKey?: string;
  readonly label: string;
  readonly icon?: string;
  readonly href: string;
  readonly availability: 'available' | 'disabled' | 'hidden';
  readonly children?: readonly NavigationEntry[];
}

/** Reconstructed from the URL, never persisted as navigation selection. */
export interface RouteContext {
  readonly routeKey: string;
  readonly moduleKey: string;
  readonly areaKey?: string;
  readonly href: string;
}

export interface PageDescriptor extends RouteContext {
  readonly serverRouteName: string;
  readonly title: string;
}
