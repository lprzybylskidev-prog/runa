export type AsyncContentState =
  'loading' | 'refreshing' | 'error' | 'empty' | 'success';
export type StatisticsMode = 'global' | 'filtered';

export type ContentLoadingShape =
  'text' | 'form' | 'detail' | 'image' | 'navigation';
export interface ContentLoadingProps {
  label?: string;
  loading?: boolean;
  refreshing?: boolean;
  shape?: ContentLoadingShape;
  lines?: number;
}
