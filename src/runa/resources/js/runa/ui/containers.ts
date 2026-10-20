export interface TabItem {
  readonly id: string;
  readonly label: string;
  readonly disabled?: boolean;
}
export interface BreadcrumbItem {
  readonly label: string;
  readonly href?: string;
}
